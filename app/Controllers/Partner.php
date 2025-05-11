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