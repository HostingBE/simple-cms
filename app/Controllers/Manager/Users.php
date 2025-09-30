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

public function view(Request $request,Response $response) {

$user = Sentinel::findById($request->getattribute('id'));


return $this->view->render($response,"manager/view-user.twig",['meta' => $meta, 'huidig' => 'manager-gebruikers-bekijken','user' => $user]);
}

public function delete(Request $request,Response $response) {    

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



public function overview(Request $request,Response $response) {

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

  return $this->view->render($response,"manager/users-overview.twig",['meta' => $meta, 'huidig' => 'manager-gebruikers-overzicht','users' => $users,'paginator' => $pagelinks]);
  }
}



?>