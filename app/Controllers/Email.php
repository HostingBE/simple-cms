<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;
use App\Models\UserModel;
use App\Models\WWvergetenModel;
use Cartalyst\Sentinel\Native\Facades\Sentinel as Sentinel;


class Email {
	
protected $view;
protected $db;
protected $flash;
protected $mail;
protected $logger;
protected $settings;
protected $directory = __DIR__ . '/../../public_html/uploads/';
	
	
public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings) {
$this->view = $view;
$this->db = $db;
$this->flash = $flash;
$this->mail = $mail;
$this->logger = $logger;
$this->settings = $settings;
}

public function view(Request $request,Response $response)  {

$sql = $this->db->prepare("SELECT body FROM email WHERE code=:code AND email=:hash");
$sql->bindparam(":code",$request->getAttribute('code'),PDO::PARAM_STR);
$sql->bindparam(":hash",$request->getAttribute('hash'),PDO::PARAM_STR);
$sql->execute();
$email = $sql->fetch(PDO::FETCH_OBJ);

if (strlen($email->body) < 10) {
	 $response->getbody()->write('deze e-mail konden we niet terugvinden met de opgegeven gegevens!');
	 return $response;
     }

$response->getbody()->write($email->body);
return $response;
 		}
}

?>