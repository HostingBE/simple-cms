<?php


namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;
use Cartalyst\Sentinel\Native\Facades\Sentinel;

class Logging {

protected $view;
protected $db;
protected $flash;
protected $mail;
protected $logger;
protected $settings;

public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings) {
$this->view = $view;
$this->db = $db;
$this->flash = $flash;
$this->mail = $mail;
$this->logger = $logger;
$this->settings = $settings;

}


public function post_bekijk_logging(Request $request,Response $response) {

  $file = $request->getAttribute('file');


  $response->getBody()->write("log" . readfile(__DIR__ . '/../../logs/'.$file));
  return $response;
   }


  public function bekijk_logging(Request $request,Response $response) {

  $files = array_diff(scandir(__DIR__."/../../logs/"), array('.', '..'));

  return $this->view->render($response,"manager/bekijk-logging.twig",['files' => $files, 'huidig' => 'bekijk-logging', 'success' => $this->flash->getFirstMessage('success'), 'errors' => $this->flash->getFirstMessage('errors')]);
    }

  public function manager_overview(Request $request,Response $response) {

$sql = $this->db->prepare("SELECT a.id,a.website,a.prio,a.log,DATE_FORMAT(a.date,'%d-%m-%Y') AS date,b.https,b.url FROM logging AS a LEFT JOIN websites AS b ON b.id=a.website ORDER BY a.id DESC LIMIT 100");
$sql->execute();
$logging = $sql->fetchALL(PDO::FETCH_OBJ);

  return $this->view->render($response,"manager/view-logging.twig",['logging' => $logging ]);
    }

  public function overview(Request $request,Response $response) {

  $user = Sentinel::getUser();

      $sql = $this->db->prepare("SELECT id,url,status,language,location,indexnow FROM websites WHERE id=:website AND user=:user");
    $sql->bindparam(":website",$request->getAttribute('website'),PDO::PARAM_INT);
    $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
    $sql->execute();
    $website = $sql->fetch(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT id,prio,log,date FROM logging WHERE user=:user AND website=:website ORDER BY id DESC");
    $sql->bindparam(":website",$request->getAttribute('website'),PDO::PARAM_INT);
    $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
$sql->execute();
$logging = $sql->fetchALL(PDO::FETCH_OBJ);



  return $this->view->render($response,"backend/view-logging.twig",['logging' => $logging,'website' => $website ]);
    }

}

?>