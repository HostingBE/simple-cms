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


class Partner {
	
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
Validator::langDir(__DIR__ . '/../../vendor/vlucas/valitron/lang/');
Validator::lang('nl');
}

public function overzicht(Request $request,Response $response)  {

$user = Sentinel::getUser();

$sql = $this->db->prepare("SELECT code FROM activations where user_id=:user LIMIT 1");
$sql->bindparam(":user",$user->id,PDO::PARAM_INT);
$sql->execute();
$code = $sql->fetch(PDO::FETCH_OBJ);


$sql = $this->db->prepare("SELECT count(id) as kliks FROM partner WHERE DATE_FORMAT(datum,'%Y-%m-%d')=DATE_FORMAT(now(),'%Y-%m-%d') AND user=:user");
$sql->bindparam(":user",$user->id,PDO::PARAM_INT);
$sql->execute();
$totalen = $sql->fetch(PDO::FETCH_OBJ);


$sql = $this->db->prepare("SELECT id,link,referal,ipadres,DATE_FORMAT(datum,'%H:%i %d-%m-%Y') AS datum FROM partner WHERE DATE_FORMAT(datum,'%Y-%m-%d')=DATE_FORMAT(now(),'%Y-%m-%d') AND user=:user LIMIT 30");
$sql->bindparam(":user",$user->id,PDO::PARAM_INT);
$sql->execute();
$statistieken = $sql->fetchALL(PDO::FETCH_OBJ);

return $this->view->render($response,'backend/mijn-partner-link.twig',['huidig' => 'partner-link','url' => $this->settings['url'],'code' => $code, 'totalen'=> $totalen,'statistieken' => $statistieken,'errors' => $this->flash->getFirstMessage('errors'),'success' => $this->flash->getFirstMessage('success'),'info' => $this->flash->getFirstMessage('info')]);
    }	
}

?>