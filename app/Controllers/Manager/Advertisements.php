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
use Slim\Exception\HttpNotFoundException;

class Advertisements {

protected $view;
protected $db;
protected $directory = __DIR__ . '/../../../public_html/uploads/';
protected $flash;
protected $logger;
protected $mail;
protected $settings;
protected $locale;
protected $translator;
protected $languages;

public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings, $locale, $translator, $languages) {
$this->view = $view;
$this->db = $db; 
$this->flash = $flash;
$this->mail = $mail;       
$this->logger = $logger;
$this->settings = $settings;
$this->locale = $locale;
$this->translator  = $translator;
$this->languages  = $languages;
Validator::langDir(__DIR__ . '/../../../vendor/vlucas/valitron/lang/');
Validator::lang($this->locale);
}


public function delete(Request $request, Response $response) {


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


public function post_edit(Request $request, Response $response) {

$id = $request->getAttribute('id');

$data =  $request->getParsedBody();

$v = new Validator($data);

$v->rule('required','naam');    
$v->rule('required','titel');
$v->rule('required','soort');
$v->rule('required','activeren');   
$v->rule('required','language');  

if (!$v->validate()) {
$first = key($v->errors());
$response->getBody()->write(json_encode(array('status' => 'error','message' => $v->errors()[$first][0])));   
return $response;
}   

$status = $data['activeren'] ?: 'p';

$sql = $this->db->prepare("UPDATE advertenties SET code=:code,naam=:naam,titel=:titel,omschrijving=:omschrijving,link=:link,script=:script,soort=:soort,status=:status,language=:language WHERE id=:id");
$sql->bindparam(":id",$id,PDO::PARAM_INT);
$sql->bindparam(":code",random(32),PDO::PARAM_STR);
$sql->bindparam(":naam",$data['naam'],PDO::PARAM_STR);
$sql->bindparam(":titel",$data['titel'],PDO::PARAM_STR);
$sql->bindparam(":omschrijving",$data['omschrijving'],PDO::PARAM_STR);
$sql->bindparam(":link",$data['link'],PDO::PARAM_STR);
$sql->bindparam(":script",$data['code'],PDO::PARAM_STR);
$sql->bindparam(":soort",$data['soort'],PDO::PARAM_STR);
$sql->bindparam(":status",$status,PDO::PARAM_STR,1);
$sql->bindparam(":language",$data['language'],PDO::PARAM_STR,2);
$sql->execute();

$response->getBody()->write(json_encode(array('status' => 'success','message' => 'advertentie succesvol bijgewerkt!'))); 
return $response;
}

public function post_add(Request $request, Response $response) {

$data =  $request->getParsedBody();

$v = new Validator($data);

$v->rule('required','naam');    
$v->rule('required','titel');
$v->rule('required','soort');
$v->rule('required','activeren');       
$v->rule('required','language');     

if (!$v->validate()) {
$first = key($v->errors());
$response->getBody()->write(json_encode(array('status' => 'error','message' => $v->errors()[$first][0])));   
return $response;
}   

$status = $data['activeren'] ?: 'p';
$counter = 0;

$sql = $this->db->prepare("INSERT INTO advertenties (code,naam,titel,omschrijving,link,script,soort,status,teller,language,datum) VALUES(:code,:naam,:titel,:omschrijving,:link,:script,:soort,:status,:counter,:language,now())");
$sql->bindparam(":code",random(32),PDO::PARAM_STR);
$sql->bindparam(":naam",$data['naam'],PDO::PARAM_STR);
$sql->bindparam(":titel",$data['titel'],PDO::PARAM_STR);
$sql->bindparam(":omschrijving",$data['omschrijving'],PDO::PARAM_STR);
$sql->bindparam(":link",$data['link'],PDO::PARAM_STR);
$sql->bindparam(":script",$data['code'],PDO::PARAM_STR);
$sql->bindparam(":soort",$data['soort'],PDO::PARAM_STR);
$sql->bindparam(":status",$status,PDO::PARAM_STR,1);
$sql->bindparam(":counter",$counter,PDO::PARAM_INT,1);
$sql->bindparam(":language",$data['language'],PDO::PARAM_STR,2);
$sql->execute();

$response->getBody()->write(json_encode(array('status' => 'success','message' => 'advertentie succesvol toegevoegd!'))); 
return $response;
}

public function advertisements(Request $request, Response $response) {

$sql = $this->db->prepare("SELECT id,code,naam,soort,titel,omschrijving,link,script,language FROM advertenties WHERE status='a' AND language=:locale ORDER BY RAND() LIMIT 1");
$sql->bindparam(":locale",$this->locale,PDO::PARAM_STR,2);
$sql->execute();
$advertentie = $sql->fetch(PDO::FETCH_OBJ);
return $this->view->render($response,'frontend/snippets/advertentie.twig',[ 'advertentie' => $advertentie ]);
}



public function edit(Request $request, Response $response) {

$id = $request->getAttribute('id');

$sql = $this->db->prepare("SELECT id,naam,soort,titel,omschrijving,link,script,language,status FROM advertenties WHERE id=:id");
$sql->bindparam(":id",$id,PDO::PARAM_INT);
$sql->execute();
$advertentie = $sql->fetch(PDO::FETCH_OBJ);

return $this->view->render($response,'manager/advertisement-edit.twig',['huidig' => 'manager-advertentie-bewerken','advertentie' => $advertentie,'languages' => array_column($this->languages,'language')]);
}


public function add(Request $request, Response $response) {

return $this->view->render($response,'manager/advertisement-add.twig',['huidig' => 'manager-advertentie-toevoegen','languages' => array_column($this->languages,'language')]);
}


public function overview(Request $request, Response $response) {

$sql = $this->db->prepare("SELECT id,naam,titel,status,soort,teller,language,datum FROM advertenties");
$sql->execute();
$advertenties = $sql->fetchALL(PDO::FETCH_OBJ);

return $this->view->render($response,'manager/advertisements-overview.twig',['huidig' => 'manager-advertenties-overzicht','advertenties' => $advertenties]);

    }
}
?>