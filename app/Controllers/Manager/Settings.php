<?php

/**
 * @author Constan van Suchtelen van de Haere <constan@hostingbe.com>
 * @copyright 2023 HostingBE
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


public function save(Request $request, Response $response) {

$excluded = array('locale','csrf_name','csrf_value');
$switches = array('advertenties','multilanguage','htmleditor','cache','html_email','disableforum','disablesupport');

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

public function overview(Request $request, Response $response) {

$sql = $this->db->prepare("SELECT setting,value FROM website_settings");
$sql->execute();
$settings = $sql->fetchALL(PDO::FETCH_OBJ);

$results = array_combine(array_column($settings, 'setting'), $settings);


return $this->view->render($response,'manager/settings-overview.twig',['huidig' => 'manager-settings-overview','meta' => $meta, 'settings' => $results ]);

}
}
?>
