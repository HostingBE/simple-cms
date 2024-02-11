<?php

/**
 * @author Constan van Suchtelen van de Haere <constan@hostingbe.com>
 * @copyright 2023 HostingBE
 */

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Gumlet\ImageResize;
use Cartalyst\Sentinel\Native\Facades\Sentinel; 
use Valitron\Validator;
use Slim\Exception\HttpNotFoundException;
require(dirname(__FILE__) .'/Captcha.class.php');


class Blog {
protected $view;
protected $db;
protected $directory = __DIR__ . '/../../public_html/uploads/';
protected $flash;
protected $logger;
protected $mail;
protected $settings;

public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings) {
$this->view = $view;
$this->db = $db; 
$this->flash = $flash;
$this->mail = $mail;       
$this->logger = $logger;
$this->settings = $settings;
}

public function post_comment(Request $request,Response $response) {

date_default_timezone_set("Europe/Amsterdam");

$data =  $request->getParsedBody();

$v = new Validator($data);

$v->rule('required','blog');    
$v->rule('required','email');
$v->rule('required','title');       
$v->rule('required','name');
$v->rule('required','message');

if (!$v->validate()) {
$first = key($v->errors());
$json = json_encode(array('status' => 'error','message' => $v->errors()[$first][0]));
$response->getBody()->write($json);   
return $response;
}   


$uitkomst = 0;
$uitkomst = eval('return ' . $_SESSION['captcha'] . ';');

$this->logger->warning(get_class() . " :Captcha voor de gebruiker is sessie " .  $uitkomst . " " . $_SESSION['captcha'] . " captcha " . $data['captcha']);     
if($uitkomst != $data['captcha']) {
      $response->getBody()->write(json_encode(array('status' => 'error','message' => 'captcha error, you did not enter a valid captcha!')));  
      return  $response;
     }
     
     unset($_SESSION['captcha']);


$status = 'p';
$code = random(15);
$user = Sentinel::getUser();

if (!$user) {
       $response->getBody()->write(json_encode(array('status' => 'error','message' => 'You need to be logged in to post comments on blog articles!')));  
       return $response;  
}

$sql = $this->db->prepare("INSERT INTO blog_reacties (code,user,blog,naam,email,titel,bericht,ipadres,status,datum) VALUES(:code,:user,:blog,:naam,:email,:titel,:bericht,:ipadres,:status,now())");
$sql->bindParam(':code',$code,PDO::PARAM_STR); 
$sql->bindParam(':user',$user->id,PDO::PARAM_INT);
$sql->bindParam(':blog',$data['blog'],PDO::PARAM_INT);        
$sql->bindParam(':naam',$data['name'],PDO::PARAM_STR);    
$sql->bindParam(':email',$data['email'],PDO::PARAM_STR);     
$sql->bindParam(':titel',$data['title'],PDO::PARAM_STR);  
$sql->bindParam(':bericht',$data['message'],PDO::PARAM_STR);  
$sql->bindParam(':ipadres',get_client_ip(),PDO::PARAM_STR); 
$sql->bindParam(':status',$status,PDO::PARAM_STR,1); 
$sql->execute();	       

$json = json_encode(array('status' => 'success','message' => 'thank you, your comment is received! Comments need to be approved by us ' . $data['email'] . '!'));
$this->logger->info('reactie voor blog geplaatst ' . $data['comment-email'] . ' ! ',array('ipadres' => $_SERVER['REMOTE_ADDR']));
$response->getBody()->write($json);
return $response;
}		


public function feed(Request $request,Response $response) {

$sql = $this->db->prepare("SELECT a.id,a.naam,a.title,a.description,DATE_FORMAT(a.date,'%Y-%m-%d') AS date,b.naam AS imagename FROM blog AS a LEFT JOIN media AS b ON b.id=a.image WHERE a.publish='y' AND a.publishdate <= now() ORDER BY date DESC");
$sql->execute();
$blogs = $sql->fetchALL(PDO::FETCH_OBJ);

$xml = $this->view->fetch('frontend/blog-newsfeed.twig',['blogs' => $blogs,'date' =>  date('d-m-Y H:i:s')]);


$response->getBody()->write($xml);
return $response->withHeader('Content-Type', 'application/xml');
}  

public function verwijder(Request $request,Response $response) {


$id = $request->getAttribute('id');

$sql = $this->db->prepare("DELETE FROM blog WHERE id=:recordID");
$sql->bindParam(':recordID',$id,PDO::PARAM_INT);
$sql->execute();

$response->getBody()->write("verwijderd!");
return $response;
}		

public function post_bewerken(Request $request,Response $response) {
$id = $request->getAttribute('id');
$data =  $request->getParsedBody();   

$v = new Validator($data);


$v->rule('required','blog-naam');   
$v->rule('required','blog-title');   
$v->rule('required','blog-description');        
$v->rule('required','blog-keywords');   
$v->rule('required','blog-categorie');
$v->rule('required','blog-tags');       
$v->rule('required','blog-user');
$v->rule('required','blog-image');
$v->rule('required','blog-content');

       if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }      

 preg_match_all("#<code>(.*?)</code>#is", $data['blog-content'], $codes);          
$replace = [];
foreach($codes[1] as $key=>$codeBlock ){
    $replace[$key] = htmlentities($codeBlock, ENT_QUOTES, "UTF-8", false);
}
unset($key, $codeBlock);

foreach($codes[0] as $key=>$replacer){
    $data['blog-content'] = str_replace($replacer, "<code>".$replace[$key]."</code>", $data['blog-content']);
}
unset($key, $replacer, $replace);

if ($data['blog-links'] == "y") {
$sql = $this->db->prepare("SELECT CONCAT('/blog-',id,'-',lower(replace(title,' ', '-')),'/') as link,tags FROM blog LIMIT 100");
$sql->execute();
$keywords = $sql->fetchALL(PDO::FETCH_OBJ);

$data['blog-content'] = (new \App\Content\InternalLinks($data['blog-content'], $keywords))->generateLinks();
}

$sql = $this->db->prepare("UPDATE blog set naam=:naam,title=:title,description=:description,keywords=:keywords,user=:user,tags=:tags,category=:category,content=:content,publish=:publish,image=:image where id=:recordID");
$sql->bindParam(':recordID',$id,PDO::PARAM_INT);
$sql->bindParam(':naam',$data['blog-naam'],PDO::PARAM_STR);    
$sql->bindParam(':title',$data['blog-title'],PDO::PARAM_STR);    
$sql->bindParam(':description',$data['blog-description'],PDO::PARAM_STR);    
$sql->bindParam(':keywords',$data['blog-keywords'],PDO::PARAM_STR);
$sql->bindParam(':user',$data['blog-user'],PDO::PARAM_STR); 
$sql->bindParam(':tags',$data['blog-tags'],PDO::PARAM_STR);    
$sql->bindParam(':content',$data['blog-content'],PDO::PARAM_STR);     
$sql->bindParam(':publish',$data['blog-publish'],PDO::PARAM_STR);  
$sql->bindParam(':image',$data['blog-image'],PDO::PARAM_STR);
$sql->bindParam(':category',$data['blog-categorie'],PDO::PARAM_INT);  
$sql->execute();		

$response->getBody()->write(json_encode(array('status' => 'success','message' => 'blog item succesvol bijgewerkt van de website!'))); 
return  $response;
}

public function post_toevoegen(Request $request,Response $response) {
$data =  $request->getParsedBody();
$user = Sentinel::getUser();

$v = new Validator($data);

$v->rule('required','blog-naam');   
$v->rule('required','blog-title');   
$v->rule('required','blog-description');        
$v->rule('required','blog-keywords');   
$v->rule('required','blog-categorie');
$v->rule('required','blog-tags');       
$v->rule('required','blog-user');
$v->rule('required','blog-image');
$v->rule('required','blog-content');
$v->rule('required','blog-publish-date');

       if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }          

if ($data['blog-publish'] == "") { $data['blog-publish'] = "n"; }

if ($data['blog-links'] == "y") {
$sql = $this->db->prepare("SELECT CONCAT('/blog-',id,'-',lower(replace(title,' ', '-')),'/') as link,tags FROM blog LIMIT 100");
$sql->execute();
$keywords = $sql->fetchALL(PDO::FETCH_OBJ);

$data['blog-content'] = (new \App\Content\InternalLinks($data['blog-content'], $keywords))->generateLinks();
}

$sql = $this->db->prepare("INSERT INTO blog (naam,title,description,keywords,user,tags,category,content,publish,image,publishdate,date) values(:naam,:title,:description,:keywords,:user,:tags,:category,:content,:publish,:image,:publishdate,now())");
$sql->bindparam(":naam",$data['blog-naam'],PDO::PARAM_STR);
$sql->bindparam(":title",$data['blog-title'],PDO::PARAM_STR);
$sql->bindparam(":description",$data['blog-description'],PDO::PARAM_STR);
$sql->bindparam(":keywords",$data['blog-keywords'],PDO::PARAM_STR);
$sql->bindparam(":user",$data['blog-user'],PDO::PARAM_INT);
$sql->bindparam(":tags",$data['blog-tags'],PDO::PARAM_STR);
$sql->bindparam(":category",$data['blog-categorie'],PDO::PARAM_INT);
$sql->bindparam(":content",$data['blog-content'],PDO::PARAM_STR); 	   	   	  
$sql->bindparam(":publish",$data['blog-publish'],PDO::PARAM_STR); 	 
$sql->bindparam(":image",$data['blog-image'],PDO::PARAM_STR); 	 
$sql->bindparam(":publishdate",$data['blog-publish-date'],PDO::PARAM_STR); 
$sql->execute();


$response->getBody()->write(json_encode(array('status' => 'success','message' => 'blog item toegevoegd aan de website!'))); 
return  $response;
}

public function bewerken(Request $request,Response $response) {

$id = $request->getAttribute('id');

$user = Sentinel::getUser();

$sql = $this->db->prepare("SELECT id,naam,title,description,keywords,user,tags,category,content,publish,publishdate,image,date from blog where id=:recordID");
$sql->bindParam(':recordID',$id,PDO::PARAM_INT);
$sql->execute();
$blog = $sql->fetch(PDO::FETCH_OBJ);   

$soort = 'w';

$sql = $this->db->prepare("SELECT id,naam from categorie where soort=:soort");
$sql->bindParam(':soort',$soort,PDO::PARAM_STR);
$sql->execute();
$categories = $sql->fetchALL(PDO::FETCH_OBJ);     

$sql = $this->db->prepare("SELECT id,first_name,last_name from users");
$sql->execute();
$users = $sql->fetchALL(PDO::FETCH_OBJ);  

$sql = $this->db->prepare("SELECT id,naam,alt from media");
$sql->execute();
$media = $sql->fetchALL(PDO::FETCH_OBJ); 


return $this->view->render($response,'manager/manager-blog-bewerken.twig',['huidig' => 'manager-blog-bewerken','blog' => $blog ,'users' => $users , 'medias' => $media,'categories' => $categories,'success' => $this->flash->getFirstMessage('success'), 'errors' => $this->flash->getFirstMessage('errors')]);

}


public function category(Request $request,Response $response) {
$meta = array();


$id = $request->getAttribute('id');
$category_name = $request->getAttribute('category');
$soort = "w";

$sql = $this->db->prepare("SELECT id,naam,soort FROM categorie WHERE soort=:soort");
$sql->bindparam(":soort",$soort,PDO::PARAM_STR,1);
$sql->execute();
$categorieen = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT a.id,a.title,a.tags,a.image,b.first_name,b.last_name,b.icon,c.naam,a.user,a.publish,DATE_FORMAT(a.date,'%d %M %y') as date,substr(content,1,150) as content,d.naam as media,d.alt,(SELECT COUNT(*) as aantal from blog_reacties where blog=a.id and status='a') AS reacties FROM blog AS a LEFT JOIN users AS b ON b.id=a.user LEFT JOIN categorie AS c ON c.id=a.category LEFT JOIN media d ON d.id=a.image WHERE a.category=:id AND a.publish='y' AND a.publishdate <= now() ORDER BY a.id DESC");
$sql->bindparam(":id",$id,PDO::PARAM_INT);

$sql->execute();
$blogs = $sql->fetchALL(PDO::FETCH_OBJ);

$meta['title']="View all blogs in " .$category_name .  " on seosite";
$meta['description']="Read all SEO articles in the category " . $category_name . ". We have " . count($blogs) . " articles for you in this category";
$meta['keywords']="articles, blog, Seo, onpage, html, basic,seosite";

return $this->view->render($response,'frontend/category-blog.twig',['huidig' => 'category-blog','meta' => $meta,'blogs' => $blogs,'categorieen' => $categorieen,'category' => $id,'category_name' => $category_name, 'url' => $this->settings['url']]);
}   
   


public function view(Request $request,Response $response) {
$meta = array();


$id = $request->getAttribute('id');
$soort = "w";

$sql = $this->db->prepare("SELECT id,naam,soort FROM categorie WHERE soort=:soort");
$sql->bindparam(":soort",$soort,PDO::PARAM_STR,1);
$sql->execute();
$categorieen = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT a.id,a.naam,a.title,a.description,a.keywords,a.tags,a.image,b.first_name,b.last_name,b.icon,c.naam,a.user,a.publish,DATE_FORMAT(a.date,'%Y-%m-%dT%H:%m:%s') as date,a.content,a.tags,a.category,CONCAT(d.naam,'.',d.extentie) AS media,d.naam AS imagename,d.width,d.height,d.alt FROM blog AS a LEFT JOIN users AS b ON b.id=a.user LEFT JOIN categorie AS c ON c.id=a.category LEFT JOIN media d ON d.id=a.image WHERE a.id=:id");
$sql->bindparam(":id",$id,PDO::PARAM_INT);
$sql->execute();
$weblog = $sql->fetch(PDO::FETCH_OBJ);

if (!$weblog) {
 throw new HttpNotFoundException($request);
 }

$weblog->content = str_replace('<h2 ','<h2 itemprop="articleSection"',$weblog->content);
$weblog->content = str_replace('</h2>','</h2><span itemprop="articleBody">',$weblog->content);
$weblog->content = str_replace('<h2 ','</span><h2 ',$weblog->content);
$sql = $this->db->prepare("SELECT a.id,a.naam,a.titel,a.bericht,DATE_FORMAT(a.datum,'%d %M') as datum,b.icon,b.id AS userid from blog_reacties AS a LEFT JOIN users AS b ON b.id=a.user WHERE a.blog=:blog AND a.status='a' ORDER BY a.datum desc LIMIT 1000");
$sql->bindparam(":blog",$id,PDO::PARAM_INT);
$sql->execute();
$berichten = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT a.id,a.title,DATE_FORMAT(a.date,'%d %M %y') as date FROM blog AS a WHERE a.publish='y' AND a.publishdate <= now() AND a.id !=:blog ORDER BY rand() LIMIT 3");
$sql->bindparam(":blog",$id,PDO::PARAM_INT);
$sql->execute();
$randomblogs = $sql->fetchALL(PDO::FETCH_OBJ);

$meta['title']=ucfirst($weblog->title);
$meta['description']=$weblog->description;
$meta['keywords']=$weblog->keywords;
$meta['url'] = parse_url($request->getUri())['path'];

$captcha = new Captcha();
$captcha->settype('webp');
$captcha->setbgcolor($this->settings['bgcolor']);
$captcha->setcolor($this->settings['color']);
$code = $captcha->create_som();
$captcha->setcode($code);
      
$_SESSION['captcha'] = $code;

$image = $captcha->base_encode();

return $this->view->render($response,'frontend/view-blog.twig',['huidig' => 'bekijk-blog','meta' => $meta,'weblog' => $weblog,'categorieen' => $categorieen, 'aantal_berichten' => count($berichten), 'berichten' => $berichten, 'url' => $this->settings['url'],'randomblogs' => $randomblogs,'captcha' => $image, 'path' => '/blog-'.$weblog->id.'-'.strtolower(str_replace(' ','-',$weblog->title)).'/']);
}	
   

public function manager_overview(Request $request,Response $response) {

$soort = "w";

$sql = $this->db->prepare("SELECT id,naam,soort FROM categorie WHERE soort=:soort");
$sql->bindparam(":soort",$soort,PDO::PARAM_STR,1);
$sql->execute();
$categorieen = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT a.id,a.title,a.tags,b.naam as categorie_naam,a.publish,DATE_FORMAT(a.date,'%d %M %y') as date,(SELECT COUNT(*) as aantal from blog_reacties where blog=a.id and status='a') AS reacties FROM blog AS a LEFT JOIN categorie AS b ON b.id=a.category");
$sql->execute();
$blogs = $sql->fetchALL(PDO::FETCH_OBJ);

return $this->view->render($response,'manager/manager-blog-overview.twig',['huidig' => 'manager-blog-overzicht','blogs' => $blogs,'categorieen' => $categorieen ]);
}    

  public function blog_toevoegen(Request $request,Response $response) { 
   
   $sql = $this->db->prepare("SELECT id,naam FROM categorie WHERE soort='w'");
   $sql->execute();
   $categories = $sql->fetchALL(PDO::FETCH_OBJ);

   $sql = $this->db->prepare("SELECT id,first_name,last_name FROM users");
   $sql->execute();
   $users = $sql->fetchALL(PDO::FETCH_OBJ);

   $sql = $this->db->prepare("SELECT id,naam,alt from media");
   $sql->execute();
   $media = $sql->fetchALL(PDO::FETCH_OBJ); 

     return $this->view->render($response,'manager/manager-blog-toevoegen.twig',['huidig' => 'manager-blog-toevoegen','categories' => $categories,'users' => $users,'medias' => $media]);
      }


public function search(Request $request,Response $response) {

$data =  $request->getParsedBody();


$soort = "w";

$sql = $this->db->prepare("SELECT id,naam,soort FROM categorie WHERE soort=:soort");
$sql->bindparam(":soort",$soort,PDO::PARAM_STR,1);
$sql->execute();
$categorieen = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT a.id,a.title,a.tags,a.image,b.first_name,b.last_name,b.icon,c.naam,a.user,a.publish,DATE_FORMAT(a.date,'%d %M %y') as date,substr(content,1,150) as content,d.naam as media,d.alt,(SELECT COUNT(*) as aantal from blog_reacties where blog=a.id and status='a') AS reacties FROM blog AS a LEFT JOIN users AS b ON b.id=a.user LEFT JOIN categorie AS c ON c.id=a.category LEFT JOIN media d ON d.id=a.image WHERE a.publish='y' AND a.publishdate <= now() AND (a.title LIKE '%".$data['q']."%' OR a.content LIKE '%".$data['q']."%') ORDER BY a.id DESC");
$sql->execute();
$blogs = $sql->fetchALL(PDO::FETCH_OBJ);

$meta['title']="Overview to improve SEO rankings and optimize our pages with onpage SEO";
$meta['description']="SeoSite articles about SEO tips on how to make your website better found by search engines. How to get higher in the results of the search engines.";
$meta['keywords']="blog,SEO,improve SEO, optmize SEO, SEO rankings,onpage SEO, link building";

return $this->view->render($response,'frontend/blog-search.twig',['huidig' => 'blog-search','meta' => $meta, 'blogs' => $blogs,'categorieen' => $categorieen, 'query' => $data['q'] ]);
}

public function overview(Request $request,Response $response) {

$soort = "w";

$sql = $this->db->prepare("SELECT id,naam,soort FROM categorie WHERE soort=:soort");
$sql->bindparam(":soort",$soort,PDO::PARAM_STR,1);
$sql->execute();
$categorieen = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT a.id,a.title,a.tags,a.image,b.first_name,b.last_name,b.icon,c.naam,a.user,a.publish,DATE_FORMAT(a.date,'%d %M %y') as date,substr(content,1,150) as content,CONCAT(d.naam,'.',d.extentie) AS media,d.naam AS imagename,d.alt,d.width,d.height,(SELECT COUNT(*) as aantal from blog_reacties where blog=a.id and status='a') AS reacties FROM blog AS a LEFT JOIN users AS b ON b.id=a.user LEFT JOIN categorie AS c ON c.id=a.category LEFT JOIN media d ON d.id=a.image WHERE a.publish='y' AND a.publishdate <= now() ORDER BY a.id DESC");
$sql->execute();
$blogs = $sql->fetchALL(PDO::FETCH_OBJ);


$meta['title']="Overview to improve SEO rankings and optimize our pages with onpage SEO";
$meta['description']="SeoSite articles about SEO tips on how to make your website better found by search engines. How to get higher in the results of the search engines.";
$meta['keywords']="blog,SEO,improve SEO, optmize SEO, SEO rankings,onpage SEO, link building";

return $this->view->render($response,'frontend/blog-overview.twig',['huidig' => 'blog','meta' => $meta, 'blogs' => $blogs,'categorieen' => $categorieen ]);

}
}
?>
