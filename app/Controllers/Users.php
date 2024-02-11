<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Slim\Exception\HttpNotFoundException;
use Valitron\Validator;
use JasonGrimes\Paginator;
use App\Models\UserModel;
use Cartalyst\Sentinel\Native\Facades\Sentinel; 


class Users {


protected $view;
protected $db;
protected $flash;
protected $mail;
protected $logger;
protected $settings;


public function __construct(Twig $view,$db,$flash,$mail,$logger,$settings) {

    $this->view = $view;
    $this->db = $db;
    $this->flash = $flash;
    $this->mail = $mail;
    $this->logger = $logger;
    $this->settings = $settings;
    Validator::langDir(__DIR__ . '/../../vendor/vlucas/valitron/lang/');
    Validator::lang('en');  
    }

public function manager_view(Request $request,Response $response) {

$user = Sentinel::findById($request->getattribute('id'));


return $this->view->render($response,"manager/bekijk-gebruiker.twig",['meta' => $meta, 'huidig' => 'manager-gebruikers-bekijken','user' => $user]);
}

public function manager_verwijder(Request $request,Response $response) {    

/*
* delete user settings and user from database
*/ 
$user = Sentinel::findById($request->getattribute('id'));

$sql = $this->db->prepare("DELETE FROM settings where user=:user");
$sql->bindparam(":user",$user->id,PDO::PARAM_INT);   
$sql->execute();

Sentinel::logout($user,true);

$user->delete();

$this->logger->warning("USERS: administrator deleted user with e-mail " . $user->email . " and id " . $user->id);

$response->getbody()->write("user successfull deleted!");
return $response;
}



public function manager_overview(Request $request,Response $response) {

$page = "1";
$start = "1";

 if ($request->getMethod() == "GET") {
          if ($request->getQueryParams()['page']) {
          $page = $request->getQueryParams()['page'];
        }
  }

$sql = $this->db->prepare("SELECT count(id) AS aantal FROM users");
$sql->execute();
$aantal = $sql->fetch(PDO::FETCH_OBJ);

// aantal pagina's bepalen
$start = $page * $this->settings['records'] - $this->settings['records']; 

$url = (string) parse_url($request->getUri())['path']  . "?page=(:num)"; 



$pagelinks = new Paginator($aantal->aantal, $this->settings['records'], $page ,  $url);
$pagelinks->setMaxPagesToShow(5);
$pagelinks->setPreviousText('previous');
$pagelinks->setNextText('next');



$sql = $this->db->prepare("SELECT a.id,a.first_name,a.last_name,a.email,a.icon,DATE_FORMAT(a.created_at,'%d-%m-%Y') AS datum,(SELECT count(*) FROM settings WHERE user=a.id) AS settings,DATEDIFF(CURRENT_DATE(),a.last_login) as aantaldagen FROM users AS a ORDER BY a.id desc LIMIT :start,:records");
$sql->bindparam(":start",$start,PDO::PARAM_INT);
$sql->bindparam(":records",$this->settings['records'],PDO::PARAM_INT);
$sql->execute();
$users = $sql->fetchALL(PDO::FETCH_OBJ);

  return $this->view->render($response,"manager/manager-gebruikers-overzicht.twig",['meta' => $meta, 'huidig' => 'manager-gebruikers-overzicht','users' => $users,'paginator' => $pagelinks]);
  }
}



?>