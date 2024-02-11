<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;
use Slim\Exception\HttpNotFoundException;

class Advertenties {

protected $view;
protected $db;
protected $directory = __DIR__ . '/../../public_html/uploads/';
protected $flash;
protected $logger;
protected $mail;
protected $settings;
protected $locale;
protected $translator;

public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings,$locale,$translator) {
$this->view = $view;
$this->db = $db; 
$this->flash = $flash;
$this->mail = $mail;       
$this->logger = $logger;
$this->settings = $settings;
$this->locale = $locale;
$this->translator  = $translator;
Validator::langDir(__DIR__ . '/../../vendor/vlucas/valitron/lang/');
Validator::lang($this->locale);
}


public function verwijder(Request $request, Response $response) {


$sql = $this->db->prepare("DELETE FROM advertenties WHERE id=:id");
$sql->bindparam(":id",$request->getAttribute('id'),PDO::PARAM_INT);
$sql->execute();

$response->getBody()->write("advertentie succesvol verwijderd!"); 
return $response;
}


public function outgoing(Request $request, Response $response) {

$id = $request->getAttribute('id');
$code = $request->getAttribute('code');

$sql = $this->db->prepare("SELECT id,link FROM advertenties WHERE id=:id AND code=:code");
$sql->bindparam(":id",$id,PDO::PARAM_INT);
$sql->bindparam(":code",$code,PDO::PARAM_STR);
$sql->execute();
$advertentie = $sql->fetch(PDO::FETCH_OBJ);

$sql = $this->db->prepare("UPDATE advertenties SET teller=teller+1 WHERE id=:id AND code=:code");
$sql->bindparam(":id",$id,PDO::PARAM_INT);
$sql->bindparam(":code",$code,PDO::PARAM_STR);
$sql->execute();

return $response->withHeader('Location',$advertentie->link)->withStatus(302);
}


public function post_bewerken(Request $request, Response $response) {

$id = $request->getAttribute('id');

$data =  $request->getParsedBody();

$v = new Validator($data);

$v->rule('required','naam');    
$v->rule('required','titel');
$v->rule('required','soort');
$v->rule('required','activeren');   

if (!$v->validate()) {
$first = key($v->errors());
$response->getBody()->write(json_encode(array('status' => 'error','message' => $v->errors()[$first][0])));   
return $response;
}   

$status = $data['activeren'] ?: 'p';

$sql = $this->db->prepare("UPDATE advertenties SET code=:code,naam=:naam,titel=:titel,omschrijving=:omschrijving,link=:link,script=:script,soort=:soort,status=:status WHERE id=:id");
$sql->bindparam(":id",$id,PDO::PARAM_INT);
$sql->bindparam(":code",random(32),PDO::PARAM_STR);
$sql->bindparam(":naam",$data['naam'],PDO::PARAM_STR);
$sql->bindparam(":titel",$data['titel'],PDO::PARAM_STR);
$sql->bindparam(":omschrijving",$data['omschrijving'],PDO::PARAM_STR);
$sql->bindparam(":link",$data['link'],PDO::PARAM_STR);
$sql->bindparam(":script",$data['code'],PDO::PARAM_STR);
$sql->bindparam(":soort",$data['soort'],PDO::PARAM_STR);
$sql->bindparam(":status",$status,PDO::PARAM_STR,1);
$sql->execute();
print_r($sql->errorInfo());exit;

$response->getBody()->write(json_encode(array('status' => 'success','message' => 'advertentie succesvol bijgewerkt!'))); 
return $response;
}

public function post_toevoegen(Request $request, Response $response) {

$data =  $request->getParsedBody();

$v = new Validator($data);

$v->rule('required','naam');    
$v->rule('required','titel');
$v->rule('required','soort');
$v->rule('required','activeren');       


if (!$v->validate()) {
$first = key($v->errors());
$response->getBody()->write(json_encode(array('status' => 'error','message' => $v->errors()[$first][0])));   
return $response;
}   

$status = $data['activeren'] ?: 'p';

$sql = $this->db->prepare("INSERT INTO advertenties (code,naam,titel,omschrijving,link,script,soort,status,datum) VALUES(:code,:naam,:titel,:omschrijving,:link,:script,:soort,:status,now())");
$sql->bindparam(":code",random(32),PDO::PARAM_STR);
$sql->bindparam(":naam",$data['naam'],PDO::PARAM_STR);
$sql->bindparam(":titel",$data['titel'],PDO::PARAM_STR);
$sql->bindparam(":omschrijving",$data['omschrijving'],PDO::PARAM_STR);
$sql->bindparam(":link",$data['link'],PDO::PARAM_STR);
$sql->bindparam(":script",$data['code'],PDO::PARAM_STR);
$sql->bindparam(":soort",$data['soort'],PDO::PARAM_STR);
$sql->bindparam(":status",$status,PDO::PARAM_STR,1);
$sql->execute();

$response->getBody()->write(json_encode(array('status' => 'success','message' => 'advertentie succesvol toegevoegd!'))); 
return $response;
}

public function advertentie(Request $request, Response $response) {

$sql = $this->db->prepare("SELECT id,code,naam,soort,titel,omschrijving,link,script FROM advertenties WHERE status='a' ORDER BY RAND() LIMIT 1");
$sql->execute();
$advertentie = $sql->fetch(PDO::FETCH_OBJ);
return $this->view->render($response,'frontend/snippets/advertentie.twig',[ 'advertentie' => $advertentie ]);
}



public function bewerken(Request $request, Response $response) {

$id = $request->getAttribute('id');

$sql = $this->db->prepare("SELECT id,naam,soort,titel,omschrijving,link,script,status FROM advertenties WHERE id=:id");
$sql->bindparam(":id",$id,PDO::PARAM_INT);
$sql->execute();
$advertentie = $sql->fetch(PDO::FETCH_OBJ);

return $this->view->render($response,'manager/advertentie-bewerken.twig',['huidig' => 'manager-advertentie-bewerken','advertentie' => $advertentie]);
}


public function toevoegen(Request $request, Response $response) {

return $this->view->render($response,'manager/advertentie-toevoegen.twig',['huidig' => 'manager-advertentie-toevoegen']);
}


public function overview(Request $request, Response $response) {

$sql = $this->db->prepare("SELECT id,naam,titel,status,soort,teller,datum FROM advertenties");
$sql->execute();
$advertenties = $sql->fetchALL(PDO::FETCH_OBJ);

return $this->view->render($response,'manager/advertenties-overzicht.twig',['huidig' => 'manager-advertenties-overzicht','advertenties' => $advertenties]);

    }
}
?>