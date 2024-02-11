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
use Mobile_Detect;

require(dirname(__FILE__) .'/Captcha.class.php');

class Contact {
	
protected $view;
protected $db;
protected $flash;
protected $mail;
protected $logger;
protected $settings;
protected $builder;


public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings,$locale) {
$this->view = $view;
$this->db = $db;
$this->flash = $flash;
$this->mail = $mail;
$this->logger = $logger;
$this->settings = $settings;
$this->locale = $locale;
}

public function postcontact(Request $request,Response $response) {
 

  
	$data =  $request->getParsedBody();

  $v = new Validator($data);

  $v->rule('required', 'name');    
  $v->rule('required', 'email');
  $v->rule('required', 'subject');
  $v->rule('required', 'captcha');
  $v->rule('required', 'message');
   
	 if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }		

     $uitkomst = 0;
     $uitkomst = eval('return ' . $_SESSION['captcha'] . ';');

     $this->logger->warning(get_class() . " : Captcha voor de gebruiker is sessie " . $_SESSION['captcha'] . " captcha " . $data['captcha']);     
     
     if($uitkomst != $data['captcha']) {
      $response->getBody()->write(json_encode(array('status' => 'error','message' => 'captcha error, no valid captcha entered!')));	
      return  $response;
     }
     
     unset($_SESSION['captcha']);
     
     
     $sql = $this->db->prepare("INSERT INTO contact (name,company,email,phone,subject,message,ip,date) values(:name,:company,:email,:phone,:subject,:message,:ip,now())");
     $sql->bindParam(':name',$data['name'],PDO::PARAM_STR);
     $sql->bindParam(':company',$data['company'],PDO::PARAM_STR);
     $sql->bindParam(':email',$data['email'],PDO::PARAM_STR);
     $sql->bindParam(':phone',$data['phone'],PDO::PARAM_STR);
     $sql->bindParam(':subject',$data['subject'],PDO::PARAM_STR);      
     $sql->bindParam(':message',$data['message'],PDO::PARAM_STR);     
     $sql->bindParam(':ip',get_client_ip(),PDO::PARAM_STR);

     $sql->execute();
   
      // mail versturen naar de bezoeker
     $mailbody = $this->view->fetch('email/contact-formulier.twig',['naam' => $data['name'],'onderwerp' => $data['subject'], 'bedrijfsnaam' => $data['company'],'email' => $data['email'], 'bericht' => $data['message'],'footer' => $this->settings['footer']]);


     // contact formulier is goed nu versturen
     $this->mail->setFrom($this->settings['email'],$this->settings['email_name']);
     $this->mail->addAddress($data['email'], $data['name']);
     $this->mail->addBCC($this->settings['emailto'], $this->settings['emailto_name']);
     $this->mail->Subject = "[".$this->settings['url']."]: contact formulier verwerkt " . date('H:i d-m-Y');
     $this->mail->Body = $mailbody;
     if ($this->settings['html_email'] == "on") { 
     $this->mail->IsHTML(true); 
     }

     if(!$this->mail->send()) {
     $this->logger->warning($this->mail->ErrorInfo);
     } else {
     $this->logger->warning('Sending of contact email was succesful!!');
     } 
     $response->getBody()->write(json_encode(array('status' => 'success','message' => 'thank your for your message, we will contact and answer your message asap!')));	
     return  $response;
     }


public function show(Request $request,Response $response) {
	    $meta['title'] = "questions or suggestions about the service of seosite, contact us";
      $meta['description'] = "having suggestions, questions or tips don't hesitate to contact us via the contact form, we will contact you as soon as possible.";   
      $meta['keywords'] = "contact, seosite, email, suggestions, questions, tips, form"; 
   
      $captcha = new Captcha();
      $captcha->settype('webp');
      $captcha->setbgcolor($this->settings['bgcolor']);
      $captcha->setcolor($this->settings['color']);
      $code = $captcha->create_som();
      $captcha->setcode($code);
      
      $_SESSION['captcha'] = $code;

      $image = $captcha->base_encode();

      $want = array('address','zipcode','city','country','coc','vat','iban');
      
      foreach($this->settings as $key => $val) {
      if (in_array($key,$want)) {
      $company[$key] = $val;
            }
      }
      return $this->view->render($response,'frontend/contact.twig',['meta' =>  $meta,'huidig' => 'contact','captcha' => $image,'company' => $company ]);
      }
}
?>
