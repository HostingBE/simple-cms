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
     
     $code = (new \App\Helpers\Helpers)->RandomString(32);

     /**
      * Encrypt sensitive data before entered in the database
      */
     $encryptedemail = (new \App\Crypt\Cryptor(getenv('secret')))->encrypt($data['email']);
     $encryptedname = (new \App\Crypt\Cryptor(getenv('secret')))->encrypt($data['name']);
     $encryptedcompany = (new \App\Crypt\Cryptor(getenv('secret')))->encrypt($data['company']);
     $encryptedsubject = (new \App\Crypt\Cryptor(getenv('secret')))->encrypt($data['subject']);     
     $encryptedmessage = (new \App\Crypt\Cryptor(getenv('secret')))->encrypt($data['message']);     
      
     $sql = $this->db->prepare("INSERT INTO contact (code,name,company,email,phone,subject,message,ip,date) values(:code, :name,:company,:email,:phone,:subject,:message,:ip,now())");
     $sql->bindParam(':code',$code,PDO::PARAM_STR,32);
     $sql->bindParam(':name',$encryptedname, PDO::PARAM_STR);
     $sql->bindParam(':company',$encryptedcompany, PDO::PARAM_STR);
     $sql->bindParam(':email',$encryptedemail, PDO::PARAM_STR);
     $sql->bindParam(':phone',$data['phone'], PDO::PARAM_STR);
     $sql->bindParam(':subject',$encryptedsubject, PDO::PARAM_STR);      
     $sql->bindParam(':message',$encryptedmessage, PDO::PARAM_STR);     
     $sql->bindParam(':ip',(new \App\Helpers\Helpers)->get_client_ip(), PDO::PARAM_STR);

     $sql->execute();
   
     /**
      * get e-mail data to send to  visitor
      */
     $mailbody = $this->view->fetch('email/contact-formulier.twig',['naam' => $data['name'],'onderwerp' => $data['subject'], 'bedrijfsnaam' => $data['company'],'email' => $data['email'], 'bericht' => $data['message'],'footer' => $this->settings['footer']]);


     // contact formulier is goed nu versturen
     $this->mail->setFrom($this->settings['email'],$this->settings['email_name']);
     $this->mail->addAddress($data['email'], $data['name']);
     $this->mail->addBCC($this->settings['emailto'], $this->settings['emailto_name']);
     $this->mail->Subject = "[".$this->settings['sitename']."]: contact formulier verwerkt " . date('H:i d-m-Y');
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
