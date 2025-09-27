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
use App\Models\UserModel;
use App\Models\WWvergetenModel;
use Cartalyst\Sentinel\Native\Facades\Sentinel as Sentinel;

require(dirname(__FILE__) .'/Captcha.class.php');

class Account {
	
protected $view;
protected $db;
protected $flash;
protected $mail;
protected $logger;
protected $settings;
protected $directory = __DIR__ . '/../../public_html/uploads/';
protected $subject;
protected $translator;
	
	
public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings, $locale, $translator) {
$this->view = $view;
$this->db = $db;
$this->flash = $flash;
$this->mail = $mail;
$this->logger = $logger;
$this->settings = $settings;
$this->locale = $locale;
$this->translator = $translator;
Validator::langDir(__DIR__ . '/../../vendor/vlucas/valitron/lang/');
Validator::lang($this->locale);
}

public function delete_account(Request $request,Response $response) {

   $user = Sentinel::getUser();
    
    /*
    * delete user settings and user from database
    */ 
    $sql = $this->db->prepare("DELETE FROM settings where user=:user");
    $sql->bindparam(":user",$user->id,PDO::PARAM_INT);   
    $sql->execute();

    Sentinel::logout($user,true);

    $user->delete();
        
    $this->logger->alert("Account: user with e-mail address " . $user->email . " is succesfull loggedoff and deleted!",array('ip-adres' => get_client_ip()));
  
    $this->flash->addMessage('success',$this->translator->get('backend.account.deleted') .  '!'); 

$response->getBody->write($this->translator->get('backend.account.deleted'));
return $response;
}

 public function activate(Request $request,Response $response) {
    
  $code =  $request->getAttribute('code'); 
  $email =  $request->getAttribute('email'); 
    
  $user = Sentinel::findByCredentials(['login' => $email,]);
  
  $Activation = Sentinel::getActivationRepository();
  
  
if ($Activation->complete($user, $code)) {
    $success = $this->translator->get('backend.account.activated') . '!';

    $code = random(32);
    $email_hash = hash('sha256', $user->email);



    // mail versturen naar de bezoeker
    $mailbody = $this->view->fetch('email/aanmelding-voltooid.twig',['naam' => $user->first_name . " " . $user->last_name,'email' => $user->email, 'url' => $this->settings['url'],'email_hash' => $email_hash,'code' => $code]);
  
   $this->setSubject("[".$this->locale."]: Register complete " . date('H:i d-m-Y'));
   
   // contact fomulier is goed nu versturen
   $this->mail->setFrom($this->settings['email'],$this->settings['email_name']);
   $this->mail->addAddress($user->email, $user->first_name . " " . $user->last_name);
   $this->mail->Subject = $this->getSubject();
   $this->mail->Body = $mailbody;
   $this->mail->IsHTML($this->settings['html_email']);

     if(!$this->mail->send()) {
     $this->flash->addMessage('errors',$this->mail->ErrorInfo);
     } else {
     $this->flash->addMessage('success',$this->translator->get('backend.account.') . '!');
     }


    /*
    * e-mail die verstuurd wordt in de datbase stoppen
    */
    $sql = $this->db->prepare("INSERT INTO email (code,onderwerp,email,user,body,datum) VALUES(:code,:onderwerp,:email,:user,:body,now())");
    $sql->bindparam(":code",$code,PDO::PARAM_STR);
    $sql->bindparam(":onderwerp",$this->getSubject(),PDO::PARAM_STR);
    $sql->bindparam(":email",$email_hash,PDO::PARAM_STR);
    $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
    $sql->bindparam(":body",$mailbody,PDO::PARAM_STR);
    $sql->execute();

    } else {
    $error = 'i\'m sorry their is no activation code active with code ' . $code . ' and e-mail ' . $email;  
    }   
    return $this->view->render($response,"frontend/activatie.twig",['meta' => $meta, 'huidig' => 'activeer-account','success' => $success, 'errors' => $error]);
}

public function delete(Request $request,Response $response)  {
	
  $code =  $request->getAttribute('code');
  $email =  $request->getAttribute('email');

$sql = $this->db->prepare("SELECT a.id,a.email,b.code FROM users AS a LEFT JOIN activations AS b ON b.user_id=a.id WHERE b.code=:code AND a.email=:email");
$sql->bindparam(":email",$email,PDO::PARAM_STR);
$sql->bindparam(":code",$code,PDO::PARAM_STR);
$sql->execute();
$userdata = $sql->fetch(PDO::FETCH_OBJ);

if ($sql->rowCount() == 0) {
    $this->logger->alert("Account: No such user with e-mail address " . $email . " and code " . $code, array('ip-adres' => get_client_ip()));
    $this->flash->addMessage('errors','No such user found with the data you supplied!'); 
    return $response->withHeader('Location','/login');   
   }

   $user = Sentinel::findById($userdata->id);
   
   Sentinel::logout($user,true);


    /*
    * delete user settings and user from database
    */ 
    $sql = $this->db->prepare("DELETE FROM settings where user=:user");
    $sql->bindparam(":user",$user->id,PDO::PARAM_INT);   
    $sql->execute();

	  $user->delete();
	    
		$this->logger->alert("Account: user with e-mail address " . $user->email . " is succesfull loggedoff and deleted!",array('ip-adres' => get_client_ip()));
  
	  $this->flash->addMessage('success','account deleted, i hope to see you back in the future!');	
    return $response->withHeader('Location','/login');
    }	
	
	
	

public function post_change_password(Request $request,Response $response)  {
  $user = array();
  $data = $request->getParsedBody();
	
	
  $v = new Validator($data); 
  $v->rule('required','account-password');
  $v->rule('length','account-password',8,32);
  $v->rule('required','account-password-new');
  $v->rule('length','account-password-new',8,32);  
  $v->rule('required','account-password-confirm');  
  $v->rule('length','account-password-confirm',8,32);
  $v->rule('equals', 'account-password-new', 'account-password-confirm');
  
	 if (!$v->validate()) {
        $this->flash->addMessage('errors',$v->errors());
        return $response->withHeader('Location','/change-password')->withStatus(302);  
        }	
  
    $user = Sentinel::getUser();
            
     try {
   				 Sentinel::update($user, ['password' => $data['account-password-new']]);
				} catch (NotUniquePasswordException $e) {
    // Handle the error here
	}
  
	    $this->flash->addMessage('success','your password has changed, do not forget to update your account info!');	
      return $response->withHeader('Location','/change-password')->withStatus(302);
}

public function confirm_password(Request $request,Response $response) {	
	
	$gebruiker = $request->getAttribute('gebruiker');
	$email = $request->getAttribute('email');
	$code = $request->getAttribute('code');		
 
  $aantal = WWvergetenModel::where('email','=',$email)->where('code','=',$code)->count();

  if ($aantal == 0) {
  $this->flash->addMessage( 'errors','request password info not found with the data you supplied!');
  $this->logger->warning('password confirm ' . $request->getAttribute('email') . ' does not exist!'); 	
 	return $response->withHeader('Location','/request-password')->withStatus(302);	 
  }
 $user = Sentinel::findByCredentials(['login' => $request->getAttribute('email')]);
  
  // create a random code
  $password = random(32);
     
  $mailbody = $this->view->fetch('email/new-password.twig',['email' => $user->email,'user' => $user->id,'firstname' => $user->first_name, 'lastname' => $user->last_name,'password' => $password, 'footer' => $this->settings['footer']]);
  
  
  // wachtwoord vergeten email
   $this->mail->setFrom($this->settings['email'],$this->settings['email_name']);
   $this->mail->addAddress($user->email, $user->first_name . " " . $user->last_name);
   $this->mail->Subject = 'Your new password for website ' . $this->settings['url'];
   $this->mail->Body = $mailbody;
   $this->mail->IsHTML($this->settings['html_email']);

  // verwijderen wachtwoord verzoek
  WWvergetenModel::where('code',$code)->delete();
  // wachtwoord wijzigen van gebruiker
  Sentinel::getUserRepository()->update($user,array_only(['password' => $password], ['password']));
                            
if(!$this->mail->send()) {
    $this->flash->addMessage('errors',$this->mail->ErrorInfo);
    $this->logger->warning('nieuw wachtwoord e-mail versturen naar ' . $user->email . " " . $this->mail->ErrorInfo);
    return $response->withHeader('Location','/request-password')->withStatus(302);	  
    } else {
    $this->flash->addMessage('info','new password send to e-mail address ' . $email);
    $this->logger->warning('nieuw wachtwoord e-mail verstuurd naar ' . $user->email);
    return $response->withHeader('Location','/request-password')->withStatus(302);	
    }
}  
  
public function post_request_code(Request $request,Response $response) {        
    
    
    $data = $request->getParsedBody();
    
    $v = new Validator($data); 
  $v->rule('required','email');
  $v->rule('required','captcha'); 
    
     if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }       


 $uitkomst = 0;
 $uitkomst = eval('return ' . $_SESSION['captcha'] . ';');

     $this->logger->warning("REQUEST CODE: Captcha voor de gebruiker is sessie " . $_SESSION['captcha'] . " captcha " . $data['captcha']);     
     

     if($uitkomst != $data['captcha']) {
      $response->getBody()->write(json_encode(array('status' => 'error','message' => 'captcha error, your captcha input is invalid!')));    
      return  $response;
     }
     
    unset($_SESSION['captcha']);

    $sql = $this->db->prepare("SELECT a.first_name,a.last_name,a.email,b.user_id,b.code,b.id FROM users a, activations b WHERE b.user_id=a.id AND b.completed='0' AND a.email=:email");
    $sql->bindparam(":email",$data['email'],PDO::PARAM_STR);
    $sql->execute();
    $activation = $sql->fetch(PDO::FETCH_OBJ);
    
    if (!is_object($activation)) {
    $this->logger->warning('REQUEST CODE: request code e-mail ' . $data['email'] . ' does not exist!');   
    $response->getBody()->write(json_encode(array('status' => 'error','message' => 'user account with supplied e-mail address ' . $data['email'] . ' not found or already active!')));  
    return  $response;
    }

    $code = random(32);
    $email_hash = hash('sha256', $activation->email);
    $this->setSubject('Your account is one step from beeing activated!');

    $mailbody = $this->view->fetch('email/reminder-activation.twig',['url' => $this->settings['url'], 'activation' => $activation,'subject' => $this->getSubject(),'email_hash'=> $email_hash,'code'=> $code,'footer' => $this->settings['footer']]);
    // herinnering sturen naar bezoeker
    $this->mail->setFrom($this->settings['email'],$this->settings['email_name']);
    $this->mail->addAddress($activation->email, $activation->first_name . " " . $activation->last_name);
    $this->mail->addBCC($this->settings['emailto'],$this->settings['emailto_name']);
    $this->mail->Subject = $this->getSubject();
    $this->mail->Body = $mailbody;      
    $this->mail->IsHTML($this->settings['html_email']); 

    /*
    * e-mail die verstuurd wordt in de datbase stoppen
    */
    $sql = $this->db->prepare("INSERT INTO email (code,onderwerp,email,user,body,datum) VALUES(:code,:onderwerp,:email,:user,:body,now())");
    $sql->bindparam(":code",$code,PDO::PARAM_STR);
    $sql->bindparam(":onderwerp",$this->getSubject(), PDO::PARAM_STR);
    $sql->bindparam(":email",$email_hash,PDO::PARAM_STR);
    $sql->bindparam(":user",$activation->id,PDO::PARAM_INT);
    $sql->bindparam(":body",$mailbody,PDO::PARAM_STR);
    $sql->execute();


    if(!$this->mail->send()) {
                $this->logger->warning('Activation reminder e-mail send to ' . $reminder->email . " is " . $this->mail->ErrorInfo);
                } else {
                $this->logger->warning('Activtion reminder of total days is  ' . $reminder->aantaldagen . ' sent to e-mail address ' . $reminder->email);
                }  

  $response->getBody()->write(json_encode(array('status' => 'success','message' => 'request new activation code sent to e-mail address  ' . $data['email'] . ' !')));  
  return  $response;    
}

public function post_request_password(Request $request,Response $response) {		
	
	
	$data = $request->getParsedBody();
	
	$v = new Validator($data); 
  $v->rule('required','email');
  $v->rule('required','captcha'); 
	
	 if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }		

     $uitkomst = 0;
     $uitkomst = eval('return ' . $_SESSION['captcha'] . ';');

     $this->logger->warning("Captcha voor de gebruiker is sessie " . $_SESSION['captcha'] . " captcha " . $data['captcha']);     
     

     if($uitkomst != $data['captcha']) {
      $response->getBody()->write(json_encode(array('status' => 'error','message' => 'captcha error, your captcha input is invalid!')));	
      return  $response;
     }
     
     unset($_SESSION['captcha']);

 
  $gebruiker = UserModel::where('email',$data['email'])->first();

  if (!$gebruiker) {
  $this->logger->warning('wachtwoord vergeten e-mail ' . $data['email'] . ' bestaat niet !'); 	
  $response->getBody()->write(json_encode(array('status' => 'error','message' => 'user with supplied e-mail address ' . $data['email'] . ' not found!')));	
  return  $response;
  }
  // create a random code
  $code = random(15);

  $mailbody = $this->view->fetch('email/wachtwoord-vergeten.twig',['email' => $gebruiker->email,'gebruiker' => $gebruiker->id,'voornaam' => $gebruiker->first_name, 'achternaam' => $gebruiker->last_name,'code' => $code,'url' => $this->settings['url'], 'footer' => $this->settings['footer']]);

  // wachtwoord vergeten email
   $this->mail->setFrom($this->settings['email'],$this->settings['email_name']);
   $this->mail->addAddress($gebruiker->email, $gebruiker->first_name . " " . $gebruiker->last_name);
   $this->mail->Subject = "Forgotten password instructions " . $this->locale;
   $this->mail->Body = $mailbody;
   if ($this->settings['html_email'] == "on") { 
   $this->mail->IsHTML($this->settings['html_email']); 
   }
   
  // verzoek in de database stoppen
  WWvergetenModel::create([
  'gebruiker' => $gebruiker->id,
  'email' => $gebruiker->email,
  'code' => $code]);
  

 
if(!$this->mail->send()) {
    $this->logger->warning('wachtwoord vergeten e-mail versturen naar ' . $data['email'] . " " . $this->mail->ErrorInfo);
    } else {
    $this->logger->warning('wachtwoord  vergeten e-mail verstuurd naar ' . $data['email'] . " code " . $code);

    }

  $response->getBody()->write(json_encode(array('status' => 'success','message' => 'request new password sent to e-mail address  ' . $data['email'] . ' !')));	
  return  $response;	
}

public function upload_icon(Request $request,Response $response) {	
    	
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
          // verplaats het bestand naar de user uploads directory
         	if 	(!is_dir(__DIR__ ."/../../public_html/images/users/".$user->id)) {
   	      		mkdir(__DIR__ ."/../../public_html/images/users/".$user->id);
   	      		} 
          rename($this->directory."/".session_id()."/".$filename,__DIR__ ."/../../public_html/images/users/".$user->id."/".$filename);
          
          
          // updaten van de icon van de gebruiker 
           $sql = $this->db->prepare("UPDATE users set icon=:icon WHERE id=:user");
           $sql->bindParam(":icon",$filename,PDO::PARAM_STR);
           $sql->bindParam(":user",$user->id,PDO::PARAM_INT);
           $sql->execute();
   
         
          // ophalen van alle images die er nu staan
          $urls = $files = array(); $json = "";


       $response->getBody()->write(json_encode(array('status' => 'success','message' => "your icon is succesfully changed!")));
       return $response
          ->withHeader('Content-Type', 'application/json');
	     }


public function post_account_info(Request $request,Response $response) { 
    
  $data = $request->getParsedBody();

    $returnurl = $this->locale . "/";

    if ($data['locale']) {
     $returnurl = $returnurl . $data['locale']  . "/";   
    }
    
  $v = new Validator($data); 
  $v->rule('required','email');
  $v->rule('required','first_name'); 
  $v->rule('required','last_name'); 


     if (!$v->validate()) {
        $this->flash->addMessage('errors',$v->errors());
        return $response->withHeader('Location',$returnurl . 'my-account')->withStatus(302);  
        } 
$user = Sentinel::getUser();

$sql = $this->db->prepare("UPDATE users SET first_name=:first_name,last_name=:last_name,email=:email WHERE id=:user");
$sql->bindParam(":user", $user->id , PDO::PARAM_INT);
$sql->bindParam(":email", $data['email'],PDO::PARAM_STR);
$sql->bindParam(":last_name", $data['last_name'], PDO::PARAM_STR);
$sql->bindParam(":first_name", $data['first_name'],PDO::PARAM_STR);
$sql->execute();

$this->flash->addMessage('success','the account information of your account is changed!');

    return $response->withHeader('Location',$returnurl . 'my-account')->withStatus(302);  
}

public function info(Request $request,Response $response) {	

$user = Sentinel::getUser();

$sql = $this->db->prepare("SELECT id,first_name,last_name,email,icon from users where id=:user");
$sql->bindParam(":user", $user->id , PDO::PARAM_INT);
$sql->execute();
$user = $sql->fetch(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT ip,hostname,plaats,land,apparaat,datum FROM logins WHERE user=:user ORDER BY id DESC LIMIT 10");
$sql->bindParam(":user", $user->id , PDO::PARAM_INT);
$sql->execute();
$logins = $sql->fetchALL(PDO::FETCH_OBJ);


return $this->view->render($response,'backend/account-info.twig',['huidig' => 'account-info','user' => $user, 'logins'=> $logins, 'errors' => $this->flash->getFirstMessage('errors'),'success' => $this->flash->getFirstMessage('success'),'info' => $this->flash->getFirstMessage('info')]);
}	


public function change_password(Request $request,Response $response) {	


return $this->view->render($response,'backend/change-password.twig',['huidig' => 'change-password','errors' => $this->flash->getFirstMessage('errors'),'success' => $this->flash->getFirstMessage('success'),'info' => $this->flash->getFirstMessage('info')]);
}	

public function request_code(Request $request,Response $response) { 

      $captcha = new Captcha();
      $captcha->settype('webp');
      $captcha->setbgcolor($this->settings['bgcolor']);
      $captcha->setcolor($this->settings['color']);
      $code = $captcha->create_som();
      $captcha->setcode($code);
      
      $_SESSION['captcha'] = $code;

      $image = $captcha->base_encode();

return $this->view->render($response,'frontend/request-code.twig',['huidig' => 'request-code','captcha' => $image,'errors' => $this->flash->getFirstMessage('errors'),'success' => $this->flash->getFirstMessage('success'),'info' => $this->flash->getFirstMessage('info')]);
}   



public function request_password(Request $request,Response $response) {	

      $captcha = new Captcha();
      $captcha->settype('webp');
      $captcha->setbgcolor($this->settings['bgcolor']);
      $captcha->setcolor($this->settings['color']);
      $code = $captcha->create_som();
      $captcha->setcode($code);
      
      $_SESSION['captcha'] = $code;

      $image = $captcha->base_encode();

return $this->view->render($response,'frontend/request-password.twig',['huidig' => 'request-password','captcha' => $image,'errors' => $this->flash->getFirstMessage('errors'),'success' => $this->flash->getFirstMessage('success'),'info' => $this->flash->getFirstMessage('info')]);
}	

 public function post_create_account(Request $request,Response $response) {


  $data =  $request->getParsedBody();
  $v = new Validator($data);     
  $v->rule('required','first_name');
  $v->rule('required','last_name');
  $v->rule('required','email');
  $v->rule('required','captcha');  


   
     if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }       

   if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
   $response->getBody()->write(json_encode(array('status' => 'error','message' => 'the e-mail address you supplied is not valid! '. $data['email']. '!')));   
   return  $response;
   } 


$uitkomst = 0;
$uitkomst = eval('return ' . $_SESSION['captcha'] . ';');

$this->logger->warning(get_class() . " Captcha voor de gebruiker is sessie " .  $uitkomst . " " . $_SESSION['captcha'] . " captcha " . $data['captcha']);     
if($uitkomst != $data['captcha']) {
      $response->getBody()->write(json_encode(array('status' => 'error','message' => 'captcha error, you did not enter a valid captcha!')));  
      return  $response;
     }
     
     unset($_SESSION['captcha']);

     if (Sentinel::findByCredentials(['login' => $data['email'],])) {
      
      $response->getBody()->write(json_encode(array('status' => 'error','message' => 'we already have an account with e-mail address '. $data['email']. '!')));   
      return  $response;
      } 

   
  $password = random(32);
   
  $user = Sentinel::register([
  'email' => $data['email'],
  'password'=> $password,
  'first_name'=> $data['first_name'],
  'icon' => '',
  'last_name' => $data['last_name'],
  'twofactor' => 'n']);

  $Activation = Sentinel::getActivationRepository();
  $activation = $Activation->create($user);
    
  $role = Sentinel::findRoleByName('customer');    
  $role->users()->attach($user);  

  $code = random(32);
  $email_hash = hash('sha256', $user->email);
  $this->setSubject("[".$this->locale."]: Confirm sign-up at website " . date('H:i d-m-Y'));

  /**
   * Create e-mail to visitor
   */
  $mailbody = $this->view->fetch('email/aanmelding-formulier.twig',['name' => $data['first_name'] . " " . $data['last_name'],'email' => $data['email'], 'password' => $password, 'activation_code' => $activation->code, 'url' => $this->settings['url'],'code'=> $code, 'email_hash' => $email_hash, 'footer' => $this->settings['footer']]);

/**
 * Send e-mail to visitor 
 */
   $this->mail->setFrom($this->settings['email'],$this->settings['email_name']);
   $this->mail->addAddress($data['email'], $data['first_name'] . " " . $data['last_name']);
   $this->mail->Subject = $this->getSubject();
   $this->mail->Body = $mailbody;
    if ($this->settings['html_email'] == "on") { 
    $this->mail->IsHTML($this->settings['html_email']); 
    }

     if(!$this->mail->send()) {
     $this->flash->addMessage('errors',$this->mail->ErrorInfo);
     } else {
     $this->flash->addMessage('success','Sending of confirmation e-mail is succesfully completed!');
     }

    /**
    *  Save e-mail in the database for online viewing
    */
    $sql = $this->db->prepare("INSERT INTO email (code,onderwerp,email,user,body,datum) VALUES(:code,:onderwerp,:email,:user,:body,now())");
    $sql->bindparam(":code",$code,PDO::PARAM_STR);
    $sql->bindparam(":onderwerp",$this->getSubject(),PDO::PARAM_STR);
    $sql->bindparam(":email",$email_hash,PDO::PARAM_STR);
    $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
    $sql->bindparam(":body",$mailbody,PDO::PARAM_STR);
    $sql->execute();

     $this->logger->info("Account: user with e-mail address " . $data['email'] . " and name " . $data['first_name'] . " " . $data['last_name'] . " is registered!");
    
     $response->getBody()->write(json_encode(array('status' => 'success','message' => 'your sign-up is completed, don\'t forget to confirm your registration via e-mail!')));  
     return  $response;  
  }

public function create_account(Request $request,Response $response) {
    
$meta['title']=$this->translator->get('meta.create-account.title');
$meta['description']=$this->translator->get('meta.create-account.description');
$meta['keywords']=$this->translator->get('meta.create-account.keywords');     

    $captcha = new Captcha();
    $captcha->settype('webp');
    $captcha->setbgcolor($this->settings['bgcolor']);
    $captcha->setcolor($this->settings['color']);
    $code = $captcha->create_som();
    $captcha->setcode($code);
      
    $_SESSION['captcha'] = $code;

    $image = $captcha->base_encode();
 
    return $this->view->render($response,"frontend/create-account.twig",['meta' => $meta, 'huidig' => 'create-account', 'captcha' => $image, 'success' => $this->flash->getFirstMessage('success'), 'errors' => $this->flash->getFirstMessage('errors')]);
    
        }

private function getSubject() {
    return $this->subject;
    }
private function setSubject($subject) {
    $this->subject = $subject;
}

}

?>
