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

namespace App\Controllers\Manager;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;
use Cartalyst\Sentinel\Native\Facades\Sentinel;


class Contacts {
	
protected $view;
protected $db;
protected $mail;
protected $logger;
protected $settings;
protected $locale;
protected $translator;
protected $user;

public function __construct(Twig $view, $db, $mail, $logger, $settings, $locale, $translator) {
$this->view = $view;
$this->db = $db;
$this->mail = $mail;
$this->logger = $logger;
$this->settings = $settings;
$this->locale = $locale;
$this->translator = $translator;
$this->user = Sentinel::getUser();
}

/**
 * Delete an conatct e-mail from the database
 * 
 */
public function delete(Request $request,Response $response) {

    $id = $request->getAttribute('id');
    $code = $request->getAttribute('code');
 
    $sql = $this->db->prepare("DELETE FROM contact WHERE id=:id AND code=:code");
    $sql->bindparam(":id",$id,PDO::PARAM_INT);
    $sql->bindparam(":code",$code,PDO::PARAM_STR,32);
    $sql->execute();
    
    $response->getBody()->write($this->translator->get('manager.contact.contact_deleted'));
    return $response;    
}

/**
 * Show the details of a conatct form 
 */
public function edit(Request $request,Response $response) {

    $id = $request->getAttribute('id');
    $code = $request->getAttribute('code');

    $sql = $this->db->prepare("SELECT id, code, name, company, email, phone, subject, message, ip, date FROM contact WHERE id=:id AND code=:code LIMIT 1");
    $sql->bindparam(":id",$id,PDO::PARAM_INT);
    $sql->bindparam(":code",$code,PDO::PARAM_STR,32);
    $sql->execute();
    $contact = $sql->fetch(PDO::FETCH_OBJ);	

    $contact->subject = (new \App\Crypt\Cryptor(getenv('secret')))->decrypt($contact->subject);
    $contact->company = (new \App\Crypt\Cryptor(getenv('secret')))->decrypt($contact->company);
    $contact->name = (new \App\Crypt\Cryptor(getenv('secret')))->decrypt($contact->name);
    $contact->email = (new \App\Crypt\Cryptor(getenv('secret')))->decrypt($contact->email);
    $contact->message = (new \App\Crypt\Cryptor(getenv('secret')))->decrypt($contact->message);

    if (filter_var($contact->ip, FILTER_VALIDATE_IP)) { 
    $sql = $this->db->prepare("SELECT ip,hostname,city,region,country,timezone FROM ipinfo WHERE ip=:ipaddress LIMIT 1");
    $sql->bindparam(":ipaddress",$contact->ip, PDO::PARAM_STR);
    $sql->execute();
    $ipinfo = $sql->fetch(PDO::FETCH_OBJ);	 
    }

    return $this->view->render($response,'manager/edit-contact.twig',['current' =>  explode('/',substr($request->getUri()->getPath(),1))[1],'meta' =>  $meta, 'contact' => $contact, 'ipinfo' => $ipinfo]);
      }

/**
 * Overview of contacts in database
 * 
 */
public function overview(Request $request,Response $response) {

	$sql = $this->db->prepare("SELECT id,code,name, company, email,subject,ip,date FROM contact LIMIT 50");
	$sql->execute();
	$contacts = $sql->fetchALL(PDO::FETCH_OBJ);	 

	$newcontacts = array();

	foreach ($contacts as $contact) {
     $contact->email = (new \App\Crypt\Cryptor(getenv('secret')))->decrypt($contact->email);
     $contact->name = (new \App\Crypt\Cryptor(getenv('secret')))->decrypt($contact->name);
     $contact->company = (new \App\Crypt\Cryptor(getenv('secret')))->decrypt($contact->company);
     $contact->subject = (new \App\Crypt\Cryptor(getenv('secret')))->decrypt($contact->subject);     
	 $newcontacts[] = (array) $contact;
	}



	      return $this->view->render($response,'manager/contact-overview.twig',['meta' =>  $meta,'current' => explode('/',substr($request->getUri()->getPath(),1))[1], 'contacts' => $newcontacts ]);
      }
}
?>