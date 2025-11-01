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
use Cartalyst\Sentinel\Native\Facades\Sentinel as Sentinel;
use Slim\Exception\HttpNotFoundException;

use App\Helpers\Captcha;

class Forum {


protected $view;
protected $db;
protected $mail;
protected $logger;
protected $settings;
protected $locale;
protected $translator;

protected $directory = __DIR__ . '/../../public_html/uploads';


	public function __construct(Twig $view, $db, $mail, $logger, $settings, $locale, $translator) {
    $this->view = $view;
    $this->db = $db;
    $this->mail = $mail;
    $this->logger = $logger;
    $this->settings = $settings;
    $this->locale = $locale;
    $this->translator = $translator;

    Validator::langDir(__DIR__ . '/../../vendor/vlucas/valitron/lang/');
    Validator::lang($this->locale);
    }

  public function post_like(Request $request,Response $response) {      
  $data =  $request->getParsedBody();
  $v = new Validator($data);
    
   $v->rule('required','id');
   $v->rule('required','source');


if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }  
$cachearr = array();
$w = false;
if (file_exists(__DIR__.'/../../tmp/topic-cache.json')) {
$cachearr = (array) json_decode(file_get_contents(__DIR__.'/../../tmp/topic-cache.json'),true);
 
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

$sql = $this->db->prepare("UPDATE forum SET {$data['source']}=${data['source']}+1 WHERE id=:id");
$sql->bindparam(":id",$data['id'],PDO::PARAM_INT);
$sql->execute();


$file = fopen(__DIR__ . '/../../tmp/topic-cache.json',"wb");
fwrite($file, json_encode($cachearr));
fclose($file);
}

$response->getBody()->write(json_encode(array('status' => 'success','message' => 'your like is recorded, thank you!')));
return $response;
  }

public function delete_file(Request $request,Response $response) {
 
if (is_file($this->directory . "/". session_id() . "/". $request->getAttribute('filename'))) {
unlink($this->directory . "/". session_id() . "/". $request->getAttribute('filename'));
}


return $response->withHeader('Location','/ask-question')->withStatus(302);
}


public function delete_all(Request $request,Response $response) {
 
if (is_dir($this->directory . "/". session_id() . "/")) {
$files = array_diff(scandir($this->directory . "/". session_id() . "/"), array('.', '..'));
}

foreach ($files as $file) {
unlink($this->directory."/".session_id()."/".$file);
}   

return $response->withHeader('Location','/ask-question')->withStatus(302);
}

public function get_files(Request $request,Response $response) {

if (is_dir($this->directory . "/". session_id() . "/")) {

 $files = array_diff(scandir($this->directory . "/". session_id() . "/"), array('.', '..'));

   }


return $this->view->render($response,'frontend/snippets/get-files.twig',[ 'files' => $files ]);
}  

public function topic_upload(Request $request,Response $response) {  
        
        
        $uploadedFiles = (array) ($request->getUploadedFiles() ?? []);



         $user = Sentinel::getUser();
     
         $max_upload = (int)(ini_get('upload_max_filesize') * 1024 * 1024);
         $max_post = (int)(ini_get('post_max_size') * 1024 * 1024);
         $memory_limit = (int)(ini_get('memory_limit') * 1024 * 1024);
         $upload_mb = min($max_upload, $max_post, $memory_limit);     




           if (!$uploadedFiles['file']->getSize()) {
           $response->getBody()->write(json_encode(array('status' => 'error','message' => "no file selected to upload!")));
           return $response;
           }

           if ($uploadedFiles['file']->getSize() > $upload_mb) {
         $response->getBody()->write(json_encode(array('status' => 'error','message' => "File to large " . $uploadedFiles['file']->getSize() . " size limit " . $upload_mb . " to large to upload!")));
           return $response;
           }

           
           if (!is_dir($this->directory . "/".session_id())) {
         mkdir($this->directory . "/". session_id());
         }
         

          // handle single input with single file upload
          $uploadedFile = $uploadedFiles['file'];
          if ($uploadedFile->getError() === \UPLOAD_ERR_OK) {
          $filename = moveUploadedFile($this->directory . "/". session_id() ."/", $uploadedFile);
          $this->logger->warning("product bestand geupload " . $filename . " vanuit de directory " . session_id() . " voor gebruiker " . $user->email);
          }

       $response->getBody()->write(json_encode(array('status' => 'success','message' => "your file is succesfully uploaded!")));
       return $response
          ->withHeader('Content-Type', 'application/json');
         }


public function postreply(Request $request, Response $response) {

$data =  $request->getParsedBody();

$v = new Validator($data);

$v->rule('required','message');
$v->rule('required','topic');

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

$user = Sentinel::getUser();

if (!$user->id) {
     $response->getBody()->write(json_encode(array('status' => 'error','message' => 'You need to login to post a message on the SEO forum!')));  
      return $response; 
      } 


if (strlen($data['message']) < 20) {
     $response->getBody()->write(json_encode(array('status' => 'error','message' => 'Your reply message is to short minimum needed 20 characters!')));  
      return $response; 
      } 
$name = $user->first_name ." ".  $user->last_name;
$sql = $this->db->prepare("INSERT INTO forum_reply (user,name,email,topic,message,date) VALUES(:user,:name,:email,:topic,:message,now())");
$sql->bindparam(":user",$user->id,PDO::PARAM_INT);
$sql->bindparam(":name",$name,PDO::PARAM_STR);
$sql->bindparam(":email",$user->email,PDO::PARAM_STR);
$sql->bindparam(":topic",$data['topic'],PDO::PARAM_INT);
$sql->bindparam(":message",nl2br($data['message']),PDO::PARAM_STR);
$sql->execute();

/*
* check if the originator wants an e-mail
*/
$sql = $this->db->prepare("SELECT a.id,a.title,a.notify,b.id AS userid,b.first_name,b.last_name,b.email FROM forum AS a LEFT JOIN users AS b ON b.id=a.user WHERE a.id=:id");
$sql->bindparam(":id",$data['topic'],PDO::PARAM_INT);
$sql->execute();
$notify = $sql->fetch(PDO::FETCH_OBJ);

if ($notify->notify == "y") {

    $code = random(32);
    $email_hash = hash('sha256', $notify->email);
    
    $this->setSubject('New activity on ' . $notify->title);

 
    $mailbody = $this->view->fetch('email/notify-topic.twig',[ 'time' => date('H:i:s d-m-Y'),'notify' => $notify,'email_hash'=> $email_hash,'code'=> $code ]);
  
  
    // wachtwoord vergeten email
    $this->mail->setFrom($this->settings['email'],$this->settings['email_name']);
   $this->mail->addAddress($notify->email, $notify->first_name . " " . $notify->last_name);
   $this->mail->Subject = $this->getSubject();
   $this->mail->Body = $mailbody;
    $this->mail->isHTML(true);

    if(!$this->mail->send()) {
    $this->logger->warning(get_class() . ': nieuwe notificate forum melding verstuurd ' . $notify->email . " " . $this->mail->ErrorInfo);
    } else {
    $this->logger->warning(get_class() . ': nieuwe notificatie forum melding verstuurd ' . $notify->email);
    }   

    /*
    * e-mail die verstuurd wordt in de datbase stoppen
    */

    $sql = $this->db->prepare("INSERT INTO email (code,onderwerp,email,user,body,datum) VALUES(:code,:onderwerp,:email,:user,:body,now())");
    $sql->bindparam(":code",$code,PDO::PARAM_STR);
    $sql->bindparam(":onderwerp",$this->getSubject(), PDO::PARAM_STR);
    $sql->bindparam(":email",$email_hash,PDO::PARAM_STR);
    $sql->bindparam(":user",$notify->userid,PDO::PARAM_INT);
    $sql->bindparam(":body",$mailbody,PDO::PARAM_STR);
    $sql->execute();

}


$json = json_encode(array('status' => 'success','message' => 'thank you, your message is received! Messages need to be approved by us !'));
$this->logger->info('niew reply geplaatst op het forum ' . $data['topic'] . ' ! ',array('ipadres' => get_client_ip()));
$response->getBody()->write($json);
return $response;
}  

public function postask(Request $request, Response $response) {

$data =  $request->getParsedBody();

$v = new Validator($data);

$v->rule('required','title');       
$v->rule('required','message');
$v->rule('required','tags'); 
$v->rule('required','category');  

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

$user = Sentinel::getUser();

if (!$user->id) {
     $response->getBody()->write(json_encode(array('status' => 'error','message' => 'You need to login to post a message on the forum!')));  
      return $response; 
      } 
$sql = $this->db->prepare("SELECT forum_name FROM settings WHERE user=:user");
$sql->bindparam(":user",$user->id,PDO::PARAM_INT);
$sql->execute();
$settings = $sql->fetch(PDO::FETCH_OBJ);

 $data['notify'] = $data['notify'] ?: 'n';
 $views = $views ?: 0;
 
 $name = $settings->forum_name ?: $user->first_name . " " . $user->last_name;
 /* replace forward slash due to problems in url rewrite */

 $data['title'] = str_replace("/","-",$data['title']);

 
 $sql = $this->db->prepare("INSERT INTO forum (user,name,email,category,title,message,notify,views,tags,language,date) VALUES(:user,:name,:email,:category,:title,:message,:notify,:views,:tags,:language, now())");
 $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
 $sql->bindparam(":name",$name,PDO::PARAM_STR);
 $sql->bindparam(":email",$user->email,PDO::PARAM_STR); 
 $sql->bindparam(":category",$data['category'],PDO::PARAM_INT);
 $sql->bindparam(":title",$data['title'],PDO::PARAM_STR);
 $sql->bindparam(":message",nl2br($data['message']),PDO::PARAM_STR);
 $sql->bindparam(":notify",$data['notify'],PDO::PARAM_STR);
 $sql->bindparam(":views",$views,PDO::PARAM_INT); 
 $sql->bindparam(":tags",$data['tags'],PDO::PARAM_STR); 
 $sql->bindparam(":language",$this->locale,PDO::PARAM_STR,2); 
 $sql->execute();

$topic = $this->db->lastinsertid();

$files = array();

if (is_dir($this->directory . "/". session_id() . "/")) {
$files = array_diff(scandir($this->directory . "/". session_id() . "/"), array('.', '..'));
}

if (count($files) != 0) {
$sql = $this->db->prepare("INSERT INTO forum_files (topic,name,size) VALUES(:topic,:name,:size)");

if (!is_dir($this->directory."/forum/")) {
        mkdir($this->directory."/forum/");
        }
if (!is_dir($this->directory."/forum/".$topic."/")) {
        mkdir($this->directory."/forum/".$topic."/");
        }



foreach ($files as $file) {
rename($this->directory."/".session_id()."/".$file, $this->directory."/forum/".$topic."/".$file);
$size = filesize($this->directory."/forum/".$topic."/".$file);
$sql->bindparam(":topic",$topic,PDO::PARAM_INT);
$sql->bindparam(":name",$file,PDO::PARAM_STR);
$sql->bindparam(":size",$size,PDO::PARAM_INT);
$sql->execute();
      }
}


$json = json_encode(array('status' => 'success','message' => 'thank you, your message is received! Messages need to be approved by us ' . $data['email'] . '!'));
$this->logger->info('nieuw bericht geplaatst op het forum ' . $data['title'] . ' ! ',array('ipadres' => get_client_ip()));
$response->getBody()->write($json);
return $response;
}  

public function view(Request $request, Response $response) {

$display = "d-none";


$sql = $this->db->prepare("UPDATE forum SET views=views+1 WHERE id=:id");
 $sql->bindparam(":id",$request->getAttribute('id'),PDO::PARAM_INT);
 $sql->execute();

 
 $sql = $this->db->prepare("SELECT a.id,a.user,a.name,a.title,a.message,a.views,a.down,a.up,a.tags,a.date,(SELECT count(*) FROM forum_reply WHERE topic=:id) AS replies,b.naam,b.id as categorie_id,c.first_name,c.last_name,c.icon,c.id AS userid FROM forum AS a LEFT JOIN categorie AS b ON b.id=a.category LEFT JOIN users AS c ON c.id=a.user WHERE a.id=:id");
 $sql->bindparam(":id",$request->getAttribute('id'),PDO::PARAM_INT);
 $sql->execute();
 $topic = $sql->fetch(PDO::FETCH_OBJ);

   if (!$topic) {
      $this->logger->info("FORUM topic " . $id . " bestaat niet opgezocht voor bezoeker",array('ip-address' => get_client_ip()));
      throw new HttpNotFoundException($request);
      }

 $sql = $this->db->prepare("SELECT a.id,a.name,a.message,a.date,b.icon,b.id as userid FROM forum_reply AS a LEFT JOIN users AS b ON b.id=a.user WHERE a.topic=:topic ORDER BY a.date DESC LIMIT 100");
 $sql->bindparam(":topic",$request->getAttribute('id'),PDO::PARAM_INT);
 $sql->execute();
 $replies = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT id,name,size FROM forum_files WHERE topic=:topic");
$sql->bindparam(":topic",$request->getAttribute('id'),PDO::PARAM_INT);
$sql->execute();
$files = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT a.id,a.naam,(SELECT count(*) FROM forum AS b WHERE b.category=a.id) AS totals FROM categorie AS a WHERE a.soort='f' ORDER BY a.naam ASC");
$sql->execute();
$categories = $sql->fetchALL(PDO::FETCH_OBJ);

if ($request->getQueryParams()['display'] == "d-block") {
    $display = "d-block";
    }

$meta['title'] = $this->settings['sitename'] . ": " . strtolower($topic->title);
$meta['description'] = substr($topic->message,0,200);
$meta['keywords'] = "";
$meta['url'] = parse_url($request->getUri())['path'];

$captcha = new Captcha();
$captcha->settype('webp');
$captcha->setbgcolor($this->settings['bgcolor']);
$captcha->setcolor($this->settings['color']);
$code = $captcha->create_som();
$captcha->setcode($code);
      
$_SESSION['captcha'] = $code;

$image = $captcha->base_encode();


    return $this->view->render($response,'frontend/view-topic.twig',['current' =>  substr($request->getUri()->getPath(),1),'huidig' => 'view-topic', 'meta' => $meta, 'categories' => $categories,'topic' => $topic,'replies' => $replies, 'files' => $files,'display' => $display,'captcha' => $image, 'path' => str_replace(' ','-',$topic->title) . '/topic-'.$topic->id.'/']);

    }  

public function ask(Request $request, Response $response) {



$sql = $this->db->prepare("SELECT id,naam FROM categorie WHERE soort='f' AND language=:locale ORDER BY naam ASC");
$sql->bindparam(":locale",$this->locale,PDO::PARAM_STR,2);
$sql->execute();
$categories = $sql->fetchALL(PDO::FETCH_OBJ);


$meta['title'] = $this->translator->get('meta.ask-question.title');
$meta['description'] = $this->translator->get('meta.ask-question.description');
$meta['keywords'] = $this->translator->get('meta.ask-question.keywords');
$meta['url'] = parse_url($request->getUri())['path'];


    $captcha = new Captcha();
    $captcha->settype('webp');
    $captcha->setbgcolor($this->settings['bgcolor']);
    $captcha->setcolor($this->settings['color']);
    $code = $captcha->create_som();
    $captcha->setcode($code);
      
    $_SESSION['captcha'] = $code;

    $image = $captcha->base_encode();


    return $this->view->render($response,'frontend/ask-question.twig',['current' =>  substr($request->getUri()->getPath(),1),'huidig' => 'ask', 'meta' => $meta, 'categories' => $categories, 'files' => $files,'captcha' => $image]);

    }


public function overview_category(Request $request, Response $response) {

$category = array('id' => $request->getAttribute('id'),'name' => str_replace('-',' ',$request->getAttribute('name')));


$sql = $this->db->prepare("SELECT a.id,a.title,a.name,a.date,b.naam AS categorie FROM forum AS a LEFT JOIN categorie AS b ON a.category=b.id ORDER BY date DESC LIMIT 5");
$sql->execute();
$latest = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT b.id AS userid,a.user,a.name,count(*) AS total,b.icon,b.first_name,b.last_name FROM forum AS a LEFT JOIN users AS b ON b.id=a.user GROUP BY a.user ORDER BY total DESC");
$sql->execute();
$contributors = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT id,naam FROM categorie WHERE soort='f' AND id=:id ORDER BY naam ASC");
$sql->bindparam(":id",$request->getAttribute('id'), PDO::PARAM_INT);   
$sql->execute();
$categories = $sql->fetchALL(PDO::FETCH_OBJ);

for ($i = 0; $i < count($categories);$i++) {
      $sql = $this->db->prepare("SELECT a.id,a.user,a.title,a.message,a.views,a.tags,date,IFNULL(a.up,0) + IFNULL(a.down,0) AS votes,(SELECT count(*) FROM forum_reply WHERE topic=a.id) AS replies,b.forum_name,DATEDIFF(now(),a.date) AS days FROM forum AS a LEFT JOIN settings AS b ON b.user=a.user WHERE a.category=:category LIMIT " . $this->settings['records']);
      $sql->bindparam(":category",$categories[$i]->id,PDO::PARAM_INT);
      $sql->execute();
      $categories[$i]->topics = $sql->fetchALL(PDO::FETCH_OBJ);
      }


$meta['title'] = $this->settings['sitename'] . ": free forum to ask questions all related to SEO!";
$meta['description'] = "Need help about SEO on your website, help about optimizing your website. Read through all topics or start a new topic";
$meta['keywords'] = "seo,forum,control,panel,topic,off-page,on-page";
$meta['url'] = parse_url($request->getUri())['path'];

    return $this->view->render($response,'frontend/overview-forum.twig',['current' =>  substr($request->getUri()->getPath(),1),'huidig' => 'overview-forum-category', 'meta' => $meta, 'categories' => $categories, 'category' => $category,'latest' => $latest,'contributors' => $contributors ]);

    }

public function overview(Request $request, Response $response) {

    if ($this->settings['disableforum'] == "on") {
         throw new HttpNotFoundException($request);
         }

$sql = $this->db->prepare("SELECT id,naam FROM categorie WHERE soort='f' AND language=:locale ORDER BY naam ASC");
$sql->bindparam(":locale",$this->locale,PDO::PARAM_STR,2);
$sql->execute();
$categories = $sql->fetchALL(PDO::FETCH_OBJ);

for ($i = 0; $i < count($categories);$i++) {
      $sql = $this->db->prepare("SELECT a.id,a.user,a.name,a.title,a.message,a.views,a.tags,date,IFNULL(a.up,0) + IFNULL(a.down,0) AS votes,(SELECT count(*) FROM forum_reply WHERE topic=a.id) AS replies,b.forum_name,DATEDIFF(now(),a.date) AS days FROM forum AS a LEFT JOIN settings AS b ON b.user=a.user WHERE a.category=:category LIMIT 5");
      $sql->bindparam(":category",$categories[$i]->id,PDO::PARAM_INT);
      $sql->execute();
      $categories[$i]->topics = $sql->fetchALL(PDO::FETCH_OBJ);
      }


$sql = $this->db->prepare("SELECT a.id,a.title,a.name,a.date,b.naam AS categorie FROM forum AS a LEFT JOIN categorie AS b ON a.category=b.id WHERE a.language=:locale ORDER BY date DESC LIMIT 5");
$sql->bindparam(":locale",$this->locale,PDO::PARAM_STR,2);
$sql->execute();
$latest = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT b.id AS userid,a.user,a.name,count(*) AS total,b.icon,b.first_name,b.last_name FROM forum AS a LEFT JOIN users AS b ON b.id=a.user GROUP BY a.user ORDER BY total DESC");
$sql->execute();
$contributors = $sql->fetchALL(PDO::FETCH_OBJ);

// $arr = getMetaData('forum-overview',$this->locale);


$meta['title'] = $this->translator->get('meta.forum-overview.title');
$meta['description'] = $this->translator->get('meta.forum-overview.description');
$meta['keywords'] = $this->translator->get('meta.forum-overview.keywords');
$meta['url'] = parse_url($request->getUri())['path'];


    return $this->view->render($response,'frontend/overview-forum.twig',['current' =>  substr($request->getUri()->getPath(),1),'huidig' => 'forum-overview','meta' => $meta, 'categories' => $categories, 'latest' => $latest,'contributors' => $contributors]);

    }
    private function setSubject($subject) {
    $this->subject = $subject;
    }
private function getSubject() {
    return $this->subject;
    }  
}

?>
