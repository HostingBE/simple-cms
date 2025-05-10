<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;
use Cartalyst\Sentinel\Native\Facades\Sentinel; 
use Illuminate\Translation\Translator;



class Login {
	protected $view;
	protected $db;
	protected $flash;
	protected $logger;
	protected $settings;
	
	public function __construct(Twig $view,$db,$flash,$logger,$settings,$local,Translator $translator) {

	  $this->view = $view;
	  $this->db = $db;
	  $this->flash = $flash;
	  $this->logger = $logger;
	  $this->settings = $settings;
	  $this->locale = $locale;
      $this->translator = $translator;

      Validator::langDir(__DIR__ . '/../../vendor/vlucas/valitron/lang/');  
      Validator::lang($this->locale);
     }


  public function logout(Request $request,Response $response) {	
  

   $user = Sentinel::getUser();
   
   
	$this->logger->info("gebruiker met email adres " . $user->email . " is succesvol uitgelogd!",array('ip-adres' => $_SERVER['REMOTE_ADDR']));
	Sentinel::logout();   	    
	$this->flash->addMessage('success',$this->translator->get('login.loggedout'));

	return $response->withHeader('Location','/login')->withStatus(302);	
  }	
  
  
	 public function post_login(Request $request,Response $response) {

    $data =  $request->getParsedBody();

    $v = new Validator($data);
    
    $v->rule('required','email');
    $v->rule('required','password');

    if (!$v->validate()) {
    $this->flash->addMessage('errors',$v->errors());	
    return $response->withHeader('Location','/login')->withStatus(302);
    }

    $user = Sentinel::findByCredentials(['login' => $data['email']]);
   
   if (!$user) {

    $this->flash->addMessage('errors',"no such user found!"); 
    return $response->withHeader('Location','/login')->withStatus(302);   

   }

   if (!Sentinel::getActivationRepository()->completed($user)) {
    $this->logger->warning("Je account is nog niet actief voor " . $data['email'],array('ip-adres' => $_SERVER['REMOTE_ADDR']));
    $this->flash->addMessage('errors',$this->translator->get('login.not_activated'));   
    return $response->withHeader('Location',$returnurl . 'login')->withStatus(302);
   }


   if ($data['remindme'] == "y") {
         if (!Sentinel::loginAndRemember($user)) {
    $this->logger->warning('ongeldige inlog gegevens ' . $data['email']);
    
    $this->flash->addMessage('errors',"invalid username or password given!"); 
    return $response->withHeader('Location','/login')->withStatus(302);       
              }  
    } else {
    
    if (!$user = Sentinel::authenticate($data)) {
    $this->logger->warning('ongeldige inlog gegevens ' . $data['email']);
    
    $this->flash->addMessage('errors',$this->translator->get('login.invalid'));	
    return $response->withHeader('Location','/login')->withStatus(302);    }	
    }  
 

/*
* succesvol login loggen in de database
*/
$sql = $this->db->prepare("INSERT INTO logins (user,ip,hostname,plaats,land,apparaat,useragent,datum) VALUES(:user,:ip,:hostname,:plaats,:land,:apparaat,:useragent,now())");
$sql->bindparam(":user",$user->id,PDO::PARAM_INT);
$sql->bindparam(":ip",$_SESSION['info']->ip,PDO::PARAM_STR);
$sql->bindparam(":hostname",$_SESSION['info']->hostname,PDO::PARAM_STR);   
$sql->bindparam(":plaats",$_SESSION['info']->city,PDO::PARAM_STR);     
$sql->bindparam(":land",$_SESSION['info']->country,PDO::PARAM_STR);    
$sql->bindparam(":apparaat",$_SESSION['info']->apparaat,PDO::PARAM_STR);
$sql->bindparam(":useragent",$_SESSION['info']->useragent,PDO::PARAM_STR);    
$sql->execute();

    $this->logger->warning(get_class() . " gebruiker succesvol ingelogd in het systeem ",['IP-address' => $_SESSION['info']->ip,'user' => $user->email]);
    
/**
* Does the user user 2FA
*/
if ($user->twofactor == 'y') {
  return $response->withHeader('Location','/2factor-auth')->withStatus(302);
  }    
    
    
    if ($this->settings['redirect']) {
    return $response->withHeader('Location',$this->settings['redirect'])->withStatus(302); 
    } else {
    return $response->withHeader('Location',$this->settings['url'] . '/backend/dashboard')->withStatus(302);	
 	   }
    }

    public function login(Request $request,Response $response) {

    return $this->view->render($response,'frontend/login.twig',['errors' => $this->flash->getFirstMessage('errors'),'success' => $this->flash->getFirstMessage('success'),'status' => $this->flash->getFirstMessage('status')]);
    
    }
}
?>
