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