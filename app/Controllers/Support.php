<?php

/**
* @author Constan van Suchtelen van de Haere <constan.vansuchtelenvandehaere@hostingbe.com>
* @copyright 2024 - 2025 HostingBE
*
* Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
* files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy,
* modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software
* is furnished to do so, subject to the following conditions:

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
* THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF
* OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*
*/

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Gumlet\ImageResize;
use Spatie\ImageOptimizer\OptimizerChainFactory;

use App\Helpers\Captcha;
use App\Controllers\DBhelpers;

class Support {
	
protected $view;
protected $db;
protected $flash;
protected $mail;
protected $logger;
protected $settings;
protected $directory = __DIR__ . '/../../public_html/uploads/';
protected $resize = "1024";
protected $translator;
protected $languages;

  
public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings, $locale, $translator, $languages) {
$this->view = $view;
$this->db = $db;
$this->flash = $flash;
$this->mail = $mail;
$this->logger = $logger;
$this->settings = $settings;
$this->locale = $locale;
$this->translator = $translator;
$this->languages = $languages;
}


public function post_support_comment(Request $request,Response $response) {

date_default_timezone_set("Europe/Amsterdam");

$data =  $request->getParsedBody();

$v = new Validator($data);

$v->rule('required','article');    
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
       $response->getBody()->write(json_encode(array('status' => 'error','message' => 'You need to be logged in to post comments on support articles!')));  
      return $response;  
}

$sql = $this->db->prepare("INSERT INTO artikel_reacties (code,user,article,naam,email,titel,bericht,ipadres,status,datum) VALUES(:code,:user,:article,:naam,:email,:titel,:bericht,:ipadres,:status,now())");
$sql->bindParam(':code',$code,PDO::PARAM_STR); 
$sql->bindParam(':user',$user->id,PDO::PARAM_INT); 
$sql->bindParam(':article',$data['article'],PDO::PARAM_INT);        
$sql->bindParam(':naam',$data['name'],PDO::PARAM_STR);    
$sql->bindParam(':email',$data['email'],PDO::PARAM_STR);     
$sql->bindParam(':titel',$data['title'],PDO::PARAM_STR);  
$sql->bindParam(':bericht',$data['message'],PDO::PARAM_STR);  
$sql->bindParam(':ipadres',get_client_ip(),PDO::PARAM_STR); 
$sql->bindParam(':status',$status,PDO::PARAM_STR,1); 
$sql->execute();      


$json = json_encode(array('status' => 'success','message' => 'thank you, your comment is received! Comments need to be approved by us ' . $data['email'] . '!'));
$this->logger->info('reactie voor support item geplaatst ' . $data['comment-email'] . ' ! ',array('ipadres' => $_SERVER['REMOTE_ADDR']));
$response->getBody()->write($json);
return $response;
}   

    
public function delete(Request $request,Response $response) {

        $id = $request->getAttribute('id');
        
        $user = Sentinel::getUser();

        $sql = $this->db->prepare("DELETE FROM artikelen WHERE user=:user AND id=:id");
        $sql->bindparam(":id",$id,PDO::PARAM_INT);
        $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
        $sql->execute();
         
        $response->getBody()->write("support item is verwijderd!");
        return $response;  
        }   

  public function post_like(Request $request,Response $response) {      
  $data =  $request->getParsedBody();
  $v = new Validator($data);
    
   $v->rule('required','id');

if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }  
$cachearr = array();
$w = false;
if (file_exists(__DIR__.'/../../tmp/like-cache.json')) {
$cachearr = (array) json_decode(file_get_contents(__DIR__.'/../../tmp/like-cache.json'),true);
 
 if (!isset($cachearr[$data['id']])) {
    $cachearr[$data['id']][] = get_client_ip(); 
    $w = true;
} elseif (!in_array(get_client_ip(),$cachearr[$data['id']])) {
    $cachearr[$data['id']][] = get_client_ip(); 
    $w = true;
    }
} else {
 $cachearr[$data['id']][] = get_client_ip();
 $w = true;  
}


if ($w === true) {

$sql = $this->db->prepare("UPDATE artikelen SET likes=likes+1 WHERE id=:id");
$sql->bindparam(":id",$data['id'],PDO::PARAM_INT);
$sql->execute();


$file = fopen(__DIR__ . '/../../tmp/like-cache.json',"wb");
fwrite($file, json_encode($cachearr));
fclose($file);
}

$response->getBody()->write(json_encode(array('status' => 'success','message' => 'record updated')));
return $response;
  }

public function post_edit(Request $request,Response $response) {
$files = array();
$id = $request->getAttribute('id');

$data =  $request->getParsedBody();

$v = new Validator($data);
    
$v->rule('required','title');
$v->rule('required','description');
$v->rule('required','categorie');    
$v->rule('required','keywords');
$v->rule('required','artikel');  
$v->rule('required','language');  
$v->rule('length','artikel',12,4096);


if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }   
       $user = Sentinel::getUser(); 

    


     $sql = $this->db->prepare("UPDATE artikelen SET categorie=:categorie,titel=:titel,description=:description,keywords=:keywords,artikel=:artikel,tags=:tags,likes=:likes WHERE user=:user AND id=:id");
     $sql->bindparam(":id", $id, PDO::PARAM_INT);
     $sql->bindparam(":user", $user->id, PDO::PARAM_INT);
     $sql->bindparam(":titel", $data['title'], PDO::PARAM_STR);   
     $sql->bindparam(":description", $data['description'], PDO::PARAM_STR);
     $sql->bindparam(":keywords", $data['keywords'], PDO::PARAM_STR);
     $sql->bindparam(":artikel", $data['artikel'], PDO::PARAM_STR);
     $sql->bindparam(":categorie", $data['categorie'], PDO::PARAM_INT);
     $sql->bindparam(":tags", $data['tags'], PDO::PARAM_STR);
     $sql->bindparam(":likes", $data['likes'], PDO::PARAM_INT);     
     $sql->execute();

    $response->getBody()->write(json_encode(array('status' => 'success','message' => 'thanks, het support item is bijgewerkt op de website!')));
    return $response;
    }

public function post_add(Request $request,Response $response) {
  $files = array();
  $id = $request->getAttribute('id');

    
  $data =  $request->getParsedBody();


  $v = new Validator($data);
    
  $v->rule('required','title');
  $v->rule('required','description');
  $v->rule('required','categorie');    
  $v->rule('required','keywords');
  $v->rule('required','artikel');
  $v->rule('length','artikel',12,4096);
  $v->rule('required','language');

	 if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }		
 
     	 $user = Sentinel::getUser(); 


    $likes = $data['likes'] ?: 0;

     $sql = $this->db->prepare("INSERT INTO artikelen (user,categorie,titel,description,keywords,artikel,tags,likes,language,datum) VALUES(:user,:categorie,:titel,:omschrijving,:keywords,:artikel,:tags,:likes,:language,now())");
     $sql->bindparam(":user", $user->id, PDO::PARAM_INT);
     $sql->bindparam(":titel", $data['title'], PDO::PARAM_STR);   
     $sql->bindparam(":omschrijving", $data['description'], PDO::PARAM_STR);
     $sql->bindparam(":keywords", $data['keywords'], PDO::PARAM_STR);
     $sql->bindparam(":artikel", $data['artikel'], PDO::PARAM_STR);
     $sql->bindparam(":tags", $data['tags'], PDO::PARAM_STR);
     $sql->bindparam(":likes", $likes, PDO::PARAM_INT);
     $sql->bindparam(":categorie", $data['categorie'], PDO::PARAM_INT);
     $sql->bindparam(":language", $data['language'], PDO::PARAM_STR,2);
     $sql->execute();

    $artikel_id = $this->db->lastinsertid();

    for ($i = 0; $i < count($files);$i++) {
    $extention = pathinfo($files[$i])['extension'];  
    $sizenew = round(filesize($this->directory . "/support/".$user->id."/".$filename),2);  
    $sql = $this->db->prepare("INSERT INTO media (id,naam,extentie,size,datum) VALUES(?,?,?,?,now())");
    $sql->execute(["",$files[$i], $extention,$sizenew]);
    }
    $aantal = count($files);
    $files = "";
    
    $response->getBody()->write(json_encode(array('status' => 'success','message' => 'thanks, het support item is toegevoegd aan de website!')));
    return $response;
    }

public function add(Request $request,Response $response) {

$soort = "h";

$sql = $this->db->prepare("SELECT id,naam FROM categorie WHERE soort=:soort");
$sql->bindparam(":soort",$soort,PDO::PARAM_STR);
$sql->execute();
$categories = $sql->fetchALL(PDO::FETCH_OBJ);



 return $this->view->render($response,"manager/support-add.twig",['huidig' => 'manager-support-toevoegen','categories' => $categories,'languages' => array_column($this->languages,'language')]);
    	
}



public function edit(Request $request,Response $response) {

$id = $request->getAttribute('id');

$soort = "h";

$sql = $this->db->prepare("SELECT id,naam FROM categorie WHERE soort=:soort");
$sql->bindparam(":soort",$soort,PDO::PARAM_STR);
$sql->execute();
$categories = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT id,categorie,titel,description,keywords,tags,likes,artikel FROM artikelen WHERE id=:id");
$sql->bindparam(":id",$id,PDO::PARAM_INT);
$sql->execute();
$artikel = $sql->fetch(PDO::FETCH_OBJ);

$meta = array();
$meta['title'] = "bewerken " . $artikel->titel;
$meta['description'] = "bewerken " . $artikel->description;
$meta['keywords'] = $artikel->keywords;

return $this->view->render($response,"manager/support-edit.twig",['huidig' => 'manager-support-bewerken','meta' => $meta, 'artikel' => $artikel,'categories' => $categories,'languages' => array_column($this->languages,'language') ]);
}

public function view(Request $request,Response $response) {

$id = $request->getAttribute('id');

 $dbhelpers = new DBHelpers($this->db, $this->locale);
 $categories = $dbhelpers->get_support_categories();

$sql = $this->db->prepare("SELECT id,titel,description,keywords,artikel,likes,tags FROM artikelen WHERE id=:id");
$sql->bindparam(":id",$id,PDO::PARAM_INT);
$sql->execute();
$artikel = $sql->fetch(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT a.id,a.naam,a.titel,a.bericht,DATE_FORMAT(a.datum,'%d %M') as datum,b.icon,b.id as userid from artikel_reacties AS a LEFT JOIN users AS b ON b.id=a.user WHERE a.article=:article AND a.status='a' ORDER BY a.datum desc LIMIT 1000");
$sql->bindparam(":article",$id,PDO::PARAM_INT);
$sql->execute();
$berichten = $sql->fetchALL(PDO::FETCH_OBJ);


$sql = $this->db->prepare("SELECT b.id,a.naam,lower(a.titel) as titel,a.datum,lower(b.titel) AS artikel FROM artikel_reacties AS a LEFT JOIN artikelen AS b ON b.id=a.article WHERE a.status='a' ORDER BY a.id DESC LIMIT 5");
$sql->execute();
$latest = $sql->fetchALL(PDO::FETCH_OBJ);


$meta = array();
$meta['title'] = $artikel->titel;
$meta['description'] = $artikel->description;
$meta['keywords'] = $artikel->keywords;


$captcha = new Captcha();
$captcha->settype('webp');
$captcha->setbgcolor($this->settings['bgcolor']);
$captcha->setcolor($this->settings['color']);
$code = $captcha->create_som();
$captcha->setcode($code);
      
$_SESSION['captcha'] = $code;

$image = $captcha->base_encode();

return $this->view->render($response,"frontend/support-view.twig",['current' =>  substr($request->getUri()->getPath(),1),'huidig' => 'view-support','meta' => $meta, 'aantal_berichten' => count($berichten), 'artikel' => $artikel,'categories' => $categories,'berichten' => $berichten, 'latest' => $latest, 'captcha' => $image ]);
}

public function manager_overview(Request $request,Response $response) {

$soort = "h";

$sql = $this->db->prepare("SELECT id,naam FROM categorie WHERE soort=:soort");
$sql->bindparam(":soort",$soort,PDO::PARAM_STR);
$sql->execute();
$categories = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT a.id,a.categorie,b.naam,a.titel,a.description,a.keywords,a.language,DATE_FORMAT(a.datum,'%d-%m-%Y') AS datum FROM artikelen  AS a LEFT JOIN categorie  AS b ON b.id=a.categorie ORDER BY a.id DESC");
$sql->execute();
$artikelen = $sql->fetchALL(PDO::FETCH_OBJ);

  return $this->view->render($response,"manager/support-overview.twig",['huidig' => 'manager-support-overzicht','artikelen' => $artikelen ,'categories' => $categories ]);
    	
}

public function view_category(Request $request,Response $response) {

$id = $request->getAttribute('id');
$name = $request->getAttribute('name');

 $dbhelpers = new DBHelpers($this->db, $this->locale);
 $categories = $dbhelpers->get_support_categories();

$a = array_search($id, array_column($categories, 'id'));
$currentcategory = $categories[$a]->naam;


$sql = $this->db->prepare("SELECT a.id,a.titel,CONCAT(SUBSTRING_INDEX(a.artikel, '.', 3), '.') AS artikel,DATE_FORMAT(a.datum,'%d-%m-%Y') AS datum,a.likes,b.id as categoryid,b.naam AS categorienaam,(SELECT count(id) AS total FROM artikel_reacties WHERE article=a.id) AS comments FROM artikelen AS a LEFT JOIN categorie AS b ON b.id=a.categorie WHERE b.id=:categorie ORDER BY a.id DESC");
$sql->bindparam(":categorie",$id,PDO::PARAM_INT);
$sql->execute();
$articles =  $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT b.id,a.naam,lower(a.titel) as titel,a.datum,lower(b.titel) AS artikel FROM artikel_reacties AS a LEFT JOIN artikelen AS b ON b.id=a.article WHERE b.language=:locale AND a.status='a' ORDER BY a.id DESC LIMIT 5");
$sql->bindparam(":locale",$this->locale,PDO::PARAM_STR,2);
$sql->execute();
$latest = $sql->fetchALL(PDO::FETCH_OBJ);

$meta['title']="SeoSite: overview category " . $currentcategory . " of our SEO support articles shown per category";
$meta['description']="Learn more about " . $currentcategory  . ", by viewing the articles..";
$meta['keywords']="html, basics, keyword, tool, backlink, terminology,seosite";

  return $this->view->render($response,"frontend/support-category.twig",['current' =>  substr($request->getUri()->getPath(),1), 'huidig' => 'support-category','categories' => $categories,'articles' => $articles,'meta' => $meta,'latest' => $latest,'currentcategory' => $currentcategory]);
}      

public function search(Request $request,Response $response) {

$data =  $request->getParsedBody();


$v = new Validator($data);
    
$v->rule('length','q',3,21);


if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }   
 $dbhelpers = new DBHelpers($this->db, $this->locale);
 $categories = $dbhelpers->get_support_categories();


$sql = $this->db->prepare("SELECT a.id,a.titel,CONCAT(SUBSTRING_INDEX(a.artikel, '.', 3), '.') AS artikel,DATE_FORMAT(a.datum,'%d-%m-%Y') AS datum,a.likes,b.id as categoryid,b.naam AS categorienaam,(SELECT count(id) AS total FROM artikel_reacties WHERE article=a.id) AS comments FROM artikelen AS a LEFT JOIN categorie AS b ON b.id=a.categorie WHERE (a.titel LIKE '%".$data['q']."%' OR a.artikel LIKE '%". $data['q'] ."%') AND b.language=:locale ORDER BY a.id DESC");
$sql->bindparam(":locale",$this->locale,PDO::PARAM_STR,2);
$sql->execute();
$articles =  $sql->fetchALL(PDO::FETCH_OBJ);

  return $this->view->render($response,"frontend/support-search.twig",['current' =>  substr($request->getUri()->getPath(),1), 'huidig' => 'support-search','articles' => $articles, 'categories' => $categories, 'q' => $data['q']]);
      
}

public function overview(Request $request,Response $response) {

$soort = "h";
$articlesnew = $articlesread = array();

$dbhelpers = new DBHelpers($this->db, $this->locale);
$categories = $dbhelpers->get_support_categories();

$sql = $this->db->prepare("SELECT a.id,a.titel,a.artikel,DATE_FORMAT(a.datum,'%d-%m-%Y') AS datum,a.likes,b.naam AS categorienaam FROM artikelen AS a LEFT JOIN categorie AS b ON b.id=a.categorie WHERE a.language=:locale ORDER BY a.id DESC LIMIT 10");
$sql->bindparam(":locale",$this->locale,PDO::PARAM_STR,2);
$sql->execute();
$articlesnew =  $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT a.id,a.titel,a.artikel,DATE_FORMAT(a.datum,'%d-%m-%Y') AS datum,a.likes,b.naam AS categorienaam FROM artikelen AS a LEFT JOIN categorie AS b ON b.id=a.categorie WHERE a.language=:locale ORDER BY a.likes DESC LIMIT 10");
$sql->bindparam(":locale",$this->locale,PDO::PARAM_STR,2);
$sql->execute();
$articlesread =  $sql->fetchALL(PDO::FETCH_OBJ);


$meta['title']=$this->translator->get('meta.support-overview.title');
$meta['description']=$this->translator->get('meta.support-overview.description');
$meta['keywords']=$this->translator->get('meta.support-overview.keywords');

  return $this->view->render($response,"frontend/support-overview.twig",['current' =>  substr($request->getUri()->getPath(),1),'huidig' => 'support-overview','categories' => $categories,'articlesnew' => $articlesnew,'articlesread' => $articlesread,'meta' => $meta]);
    	
}
     }
 ?>    
