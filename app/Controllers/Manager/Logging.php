<?php


namespace App\Controllers\Manager;

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


public function post(Request $request,Response $response) {

  $file = $request->getAttribute('file');


  $response->getBody()->write("log" . readfile(__DIR__ . '/../../../logs/'.$file));
  return $response;
   }


  public function view(Request $request,Response $response) {

  $files = array_diff(scandir(__DIR__."/../../../logs/"), array('.', '..'));

  return $this->view->render($response,"manager/view-logging.twig",['files' => $files, 'huidig' => 'bekijk-logging', 'success' => $this->flash->getFirstMessage('success'), 'errors' => $this->flash->getFirstMessage('errors')]);
    }
}

?>