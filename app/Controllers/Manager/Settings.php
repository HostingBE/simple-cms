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



class Settings {


public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings) {
$this->view = $view;
$this->db = $db;
$this->flash = $flash;
$this->mail = $mail;
$this->logger = $logger;
$this->settings = $settings;
}

public function post(Request $request, Response $response) {

$data =  $request->getParsedBody();

$v = new Validator($data);

$v->rule('required',array('setting','value')); 
$v->rule('alpha','setting');

if (!$v->validate()) {
$first = key($v->errors());
$json = json_encode(array('status' => 'error','message' => $v->errors()[$first][0]));
$response->getBody()->write($json);   
return $response;
}   


$sql = $this->db->prepare("SELECT count(*) AS counter FROM website_settings WHERE setting=:setting");
$sql->bindparam(":setting", $data['setting'], PDO::PARAM_STR);
$sql->execute();
$total = $sql->fetch(PDO::FETCH_OBJ);

if ($total->counter > 0) {
      $response->getBody()->write(json_encode(array('status'=> 'error', 'message' => 'setting  name already exists!')));
      return $response;
      }

$sql = $this->db->prepare("INSERT INTO website_settings (setting, value) VALUES(:setting, :value)");
$sql->bindparam(":setting", $data['setting'], PDO::PARAM_STR);
$sql->bindparam(":value", $data['value'], PDO::PARAM_STR);
$sql->execute();

$this->logger->info(__CLASS__." : added website setting ".$data['setting']." to website settings");

$response->getBody()->write(json_encode(array('status'=> 'success', 'message' => 'setting  name added to your website settings!')));
return $response;
}

public function save(Request $request, Response $response) {

$excluded = array('locale','csrf_name','csrf_value');
$switches = array('advertenties','multilanguage','htmleditor','cache','html_email','disableforum','disablesupport','disablechat');

$data = $request->getParsedBody();

$sql = $this->db->prepare("SELECT id,setting,value FROM website_settings");
$sql->execute();
$settings = $sql->fetchALL(PDO::FETCH_OBJ);


foreach ($switches as $switch) {
if (array_key_exists($switch,$data) === false) {
$data[$switch] = 'off';
            }
}

foreach ($data as $key => $value) {

if (in_array($key,$excluded)) { continue; }
// print "key:" . $key . "\n";

if (array_search($key,array_column($settings, 'setting'))) {
$currentkey = array_search($key,array_column($settings, 'setting'));

// print "key " . $key  . " " . $currentkey . " met id " . $settings[$currentkey]->id ."\n";

$sql = $this->db->prepare("UPDATE website_settings SET value=:value WHERE id=:id");
$sql->bindparam(":id",$settings[$currentkey]->id,PDO::PARAM_INT);
$sql->bindparam(":value",$value,PDO::PARAM_STR);
$sql->execute();
} else {
$sql = $this->db->prepare("INSERT INTO website_settings (setting,value) VALUES(:setting,:value)");
$sql->bindparam(":setting",$key,PDO::PARAM_STR);
$sql->bindparam(":value",$value,PDO::PARAM_STR);
$sql->execute();
      }
}

$response->getBody()->write(json_encode(array('status'=> 'success', 'message' => 'your settings are saved!')));
return $response;
}


public function add(Request $request, Response $response) {

$meta = array();
return $this->view->render($response,'manager/add-setting.twig',['huidig' => 'manager-add-setting','meta' => $meta ]);

}

public function overview(Request $request, Response $response) {

$sql = $this->db->prepare("SELECT setting,value FROM website_settings");
$sql->execute();
$settings = $sql->fetchALL(PDO::FETCH_OBJ);

$results = array_combine(array_column($settings, 'setting'), $settings);


return $this->view->render($response,'manager/settings-overview.twig',['huidig' => 'manager-settings-overview','meta' => $meta, 'settings' => $results ]);

}
}
?>
