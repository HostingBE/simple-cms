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


class Links {

public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings) {
$this->view = $view;
$this->db = $db; 
$this->flash = $flash;
$this->mail = $mail;       
$this->logger = $logger;
$this->settings = $settings;
}


public function delete(Request $request, Response $response) {

$data =  $request->getParsedBody();

if (isset($data['id'])) {
for ($i = 0;$i < count($data['id']);$i++) {
        $sql = $this->db->prepare("DELETE FROM links WHERE id=:id");
        $sql->bindparam(":id",$data['id'][$i],PDO::PARAM_INT);
        $sql->execute();
        } 
    }

return $response->withHeader('Location','/manager/links-overview')->withStatus(302);
}


public function post_add(Request $request, Response $response) {

$data =  $request->getParsedBody();
$v = new Validator($data);

$v->rule('required',array('name','title','url'));  

       if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }  

$sql = $this->db->prepare("INSERT INTO links(name,title,url,category,date) VALUES(:name,:title,:url,:category,now())");
$sql->bindparam(":name",$data['name'],PDO::PARAM_STR);
$sql->bindparam(":title",$data['title'],PDO::PARAM_STR);
$sql->bindparam(":url",$data['url'],PDO::PARAM_STR);
$sql->bindparam(":category",$data['category'],PDO::PARAM_INT);
$sql->execute();

$response->getBody()->write(json_encode(array('status' => 'success','message' => 'link item added to your website!'))); 
return  $response;
}


public function overview(Request $request, Response $response) {


$sql = $this->db->prepare("SELECT id,naam FROM categorie WHERE soort='m'");
$sql->execute();
$categories = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT a.id,a.title,a.url,b.naam AS category_name FROM links AS a LEFT JOIN categorie AS b ON b.id=a.category");
$sql->execute();
$links = $sql->fetchALL(PDO::FETCH_OBJ);


return $this->view->render($response,'manager/links-overview.twig',['huidig' => 'manager-links-overview','meta' => $meta, 'links' => $links, 'categories' => $categories ]);

}


}

?>