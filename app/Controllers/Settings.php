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
use Valitron\Validator;
use Cartalyst\Sentinel\Native\Facades\Sentinel as Sentinel;


class Settings {

protected $command;
protected $logger;

public function __construct(Twig $view,$db,$flash,$logger,$settings) {
    $this->view = $view;
    $this->db = $db;
    $this->flash = $flash;
    $this->logger = $logger;
    $this->settings = $settings;
    Validator::langDir(__DIR__ . '/../../vendor/vlucas/valitron/lang/');
    Validator::lang('en');
    }


public function post_api(Request $request, Response $response) { 
  
$data = $request->getParsedBody();

$v = new Validator($data); 
$v->rule('required','ipaddress');

     if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }   

  $user = Sentinel::getUser();

 $sql = $this->db->prepare("SELECT id,ipaddress FROM api WHERE user=:user");
 $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
 $sql->execute();
 $apiobj = $sql->fetch(PDO::FETCH_OBJ);

 if (is_object($apiobj)) {

$sql = $this->db->prepare("UPDATE api SET ipaddress=:ipaddress WHERE user=:user AND id=:id");
$sql->bindparam(":user",$user->id,PDO::PARAM_INT);
$sql->bindparam(":id",$apiobj->id,PDO::PARAM_INT);
$sql->bindparam(":ipaddress",$data['ipaddress'],PDO::PARAM_STR);

$sql->execute();
 } else {
$username = random(32);
$password = random(32);


$sql = $this->db->prepare("INSERT INTO api (user,username,password,ipaddress,datum) VALUES(:user,:username,:password,:ipaddress,now())");
$sql->bindparam(":user",$user->id,PDO::PARAM_INT);
$sql->bindparam(":username",$username,PDO::PARAM_STR);
$sql->bindparam(":password",$password,PDO::PARAM_STR);
$sql->bindparam(":ipaddress",$data['ipaddress'],PDO::PARAM_STR);
$sql->execute();
}





  $response->getBody()->write(json_encode(array('status' => 'success','message' => 'API settings are saved for ' . $user['email'] . ' !')));   
  return  $response;    
}


public function save(Request $request, Response $response) { 
  

  $data = $request->getParsedBody();


    
  $v = new Validator($data); 
  $v->rule('required','email');
  $v->rule('required','language',2); 
  $v->rule('required','forum_name');    

     if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }   

  $user = Sentinel::getUser();


  $sql = $this->db->prepare("SELECT count(id) AS total FROM settings WHERE forum_name=:forum_name AND user !=:user");
  $sql->bindparam(":forum_name",$data['forum_name'],PDO::PARAM_STR);
  $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
  $sql->execute();
  $aantal = $sql->fetch(PDO::FETCH_OBJ);
if ($aantal->total > 0) {
        $response->getBody()->write(json_encode(array('status' => 'error','message' => "display name " . $data['forum_name'] .  " is not available try another display name!"))); 
        return  $response;
        }     

  $sql = $this->db->prepare("SELECT count(*) AS total FROM settings WHERE user=:user");
  $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
  $sql->execute();
  $aantal = $sql->fetch(PDO::FETCH_OBJ);
 
   if ($aantal->total == "0") {
   $sql = $this->db->prepare("INSERT INTO settings (user,email,language,forum_name,pushover_app,pushover_recipient) values(:user,:email,:language,:forum_name,:application,:recipient)");
   } else {
   $sql = $this->db->prepare("UPDATE settings SET email=:email,language=:language,forum_name=:forum_name,pushover_app=:application,pushover_recipient=:recipient WHERE user=:user");
   }
   $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
   $sql->bindparam(":email",$data['email'],PDO::PARAM_STR);
   $sql->bindparam(":language",$data['language'],PDO::PARAM_STR);
   $sql->bindparam(":forum_name",$data['forum_name'],PDO::PARAM_STR);
   $sql->bindparam(":application",$data['application'],PDO::PARAM_STR);
   $sql->bindparam(":recipient",$data['recipient'],PDO::PARAM_STR);
   $sql->execute();


  $response->getBody()->write(json_encode(array('status' => 'success','message' => 'settings are saved for ' . $user['email'] . ' !')));   
  return  $response;    
}


public function api(Request $request, Response $response) { 
  
  $user = Sentinel::getUser();

  $sql = $this->db->prepare("SELECT username,password,ipaddress FROM api WHERE user=:user");
  $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
  $sql->execute();
  $settings = $sql->fetch(PDO::FETCH_OBJ);
  return $this->view->render($response,'backend/api-settings.twig',['huidig' => 'api-settings','settings' => $settings ]);
  }

public function overview(Request $request, Response $response) { 
  
  $user = Sentinel::getUser();

  $sql = $this->db->prepare("SELECT email,language,forum_name,pushover_app,pushover_recipient FROM settings WHERE user=:user");
  $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
  $sql->execute();
  $settings = $sql->fetch(PDO::FETCH_OBJ);
     return $this->view->render($response,'backend/settings.twig',['huidig' => 'settings','settings' => $settings ]);
      }
   }
?>      