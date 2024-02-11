<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;
use Cartalyst\Sentinel\Native\Facades\Sentinel as Sentinel;

class Dashboard {


public function __construct(Twig $view,$db,$flash,$mail,$logger,$settings) {
    $this->view = $view;
    $this->db = $db;
    $this->flash = $flash;
    $this->mail = $mail;    
    $this->logger = $logger;
    $this->settings = $settings;
    }

public function manager_overview(Request $request, Response $response) {
      
            $sql = $this->db->prepare("SELECT count(id) AS total_users,(SELECT count(id) FROM artikel_reacties) AS total_support_comments,(SELECT count(id) FROM blog_reacties) AS total_blog_comments,(SELECT count(id) FROM activations WHERE completed='0') AS total_incomplete,(SELECT count(id) FROM forum) AS total_forum,(SELECT count(*) FROM forum_reply) AS total_forum_reply FROM users");
      $sql->execute();
      $totals = $sql->fetch(PDO::FETCH_OBJ);

      $sql = $this->db->prepare("SELECT ip,query,referer,date FROM site_search_history ORDER BY date DESC limit 15");
      $sql->execute();
      $searches = $sql->fetchALL(PDO::FETCH_OBJ);

 

      return $this->view->render($response,'manager/dashboard.twig',['huidig' => 'manager-dashboard','totals' => $totals, 'searches' => $searches]);
      }


public function overview(Request $request, Response $response) {
      

      return $this->view->render($response,'backend/dashboard.twig',['huidig' => 'backend-dashboard']);
      }


}





?>