<?php

/**
 * @author Constan van Suchtelen van de Haere <constan@hostingbe.com>
 * @copyright 2023 HostingBE
 */

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;
use Cartalyst\Sentinel\Native\Facades\Sentinel; 


class Manager  {
	protected $view;
	protected $db;
	protected $flash;
	protected $logger;
	protected $settings;

	public function __construct(Twig $view, $db, $flash,$mail,$logger,$settings,$languages) {

	$this->view = $view;
	$this->db = $db;
	$this->flash = $flash;
	$this->mail = $mail;
	$this->logger = $logger;	
	$this->settings = $settings;	
	$this->languages = $languages;
	}


public function verwijder_pagina(Request $request,Response $response) {

         $pagina = $request->getAttribute('pagina');
          $user = Sentinel::getUser();

        $sql = $this->db->prepare("DELETE FROM `pages` WHERE id=:pagina");
        $sql->bindparam(":pagina",$pagina,PDO::PARAM_INT);
        $sql->execute();

$response->getBody()->write("verwijderd!");
return $response;
}  

public function post_pagina_bewerken(Request $request,Response $response) {
	
     $pagina = $request->getAttribute('pagina');
	  $data = $request->getParsedBody();
	 
	 $v = new Validator($data); 
   $v->rule('required','ap-name');
    $v->rule('required','ap-titel');
    $v->rule('required','ap-description');
    $v->rule('required','ap-keywords');
     $v->rule('required','ap-content');   
     $v->rule('required','ap-language'); 

	 if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }		
 
     	 $user = Sentinel::getUser(); 	

	    $publish = $data['ap-page-publish'] ?: "n";

if ($data['ap-page-links'] == "y") {
$keywords =(new \App\Content\KeyWords($this->db))->getKeyWords();
$data['ap-content'] = (new \App\Content\InternalLinks($data['ap-content'], $keywords))->generateLinks();
}


	  $sql = $this->db->prepare("UPDATE `pages` SET name=:name,titel=:titel,description=:description,keywords=:keywords,content=:content,template=:template,publish=:publish,language=:language,publish_date=:publish_date WHERE id=:pagina");
	  $sql->bindparam(":name",$data['ap-name'],PDO::PARAM_STR);
	  $sql->bindparam(":titel",$data['ap-titel'],PDO::PARAM_STR);
	  $sql->bindparam(":description",$data['ap-description'],PDO::PARAM_STR);
	  $sql->bindparam(":keywords",$data['ap-keywords'],PDO::PARAM_STR);
	  $sql->bindparam(":content",$data['ap-content'],PDO::PARAM_STR);
	  $sql->bindparam(":template",$data['ap-template'],PDO::PARAM_STR);	
		$sql->bindparam(":publish",$publish,PDO::PARAM_STR,1);
	  $sql->bindparam(":publish_date",$data['publish_date'],PDO::PARAM_STR);
	  $sql->bindparam(":language",$data['ap-language'],PDO::PARAM_STR,2);  
	  $sql->bindparam(":pagina",$pagina,PDO::PARAM_INT);
	  $sql->execute();
	
	        $response->getBody()->write(json_encode(array('status' => 'success','message' => "website pagina succesvol bijgewerkt!"))); 
        return  $response; 
        }		

public function toevoegen(Request $request,Response $response) {
	
  return $this->view->render($response,"manager/manager-pagina-toevoegen.twig",['success' => $this->flash->getFirstMessage('success'), 'errors' => $this->flash->getFirstMessage('errors')]);
    	
}


public function post_pagina_toevoegen(Request $request,Response $response) {
	
$data = $request->getParsedBody();
	 
$v = new Validator($data); 
$v->rule('required','ap-name');
$v->rule('required','ap-titel');
$v->rule('required','ap-description');
$v->rule('required','ap-keywords');
$v->rule('required','ap-content');   
$v->rule('required','ap-template');   

if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }		

$data['ap-name'] = preg_replace("/ /","-",$data['ap-name']);
    
$user = Sentinel::getUser();   
    
$publish = $data['ap-page-publish'] ?: "n";


if ($data['ap-page-links'] == "y") {
$keywords =(new \App\Content\KeyWords($this->db))->getKeyWords();
$data['ap-content'] = (new \App\Content\InternalLinks($data['ap-content'], $keywords))->generateLinks();
}


	  $sql = $this->db->prepare("INSERT INTO `pages` (name,titel,description,keywords,content,template,publish,publish_date,language,datum) VALUES(:name,:titel,:description,:keywords,:content,:template,:publish,:publish_date,:language,now())");
	  $sql->bindparam(":name",$data['ap-name'],PDO::PARAM_STR);
	  $sql->bindparam(":titel",$data['ap-titel'],PDO::PARAM_STR);
	  $sql->bindparam(":description",$data['ap-description'],PDO::PARAM_STR);
	  $sql->bindparam(":keywords",$data['ap-keywords'],PDO::PARAM_STR);
	  $sql->bindparam(":content",$data['ap-content'],PDO::PARAM_STR);
	  $sql->bindparam(":template",$data['ap-template'],PDO::PARAM_STR);	  
	  $sql->bindparam(":publish",$publish,PDO::PARAM_STR,1);
	  $sql->bindparam(":publish_date",$data['publish_date'],PDO::PARAM_STR);
	  $sql->bindparam(":language",$data['ap-language'],PDO::PARAM_STR,2);	 
	  $sql->execute();

    $response->getBody()->write(json_encode(array('status' => 'success','message' => "website pagina succesvol toegevoegd!"))); 
    return  $response; 
    }	

public function pagina_bewerken(Request $request,Response $response) {
$pagina = $request->getAttribute('pagina');
	
	
$sql = $this->db->prepare("SELECT id,name,titel,description,keywords,content,template,publish,language,publish_date,DATE_FORMAT(datum,'%d-%m-%Y') as datum FROM `pages` WHERE id=:pagina");
$sql->bindparam(":pagina",$pagina,PDO::PARAM_INT);
$sql->execute();
$pagina = $sql->fetch(PDO::FETCH_OBJ);

$templates =   $files = array_diff(scandir(__DIR__."/../../templates/frontend/"), array('.', '..'));

  return $this->view->render($response,"manager/manager-pagina-bewerken.twig",['huidig' => 'manager-pagina-bewerken','pagina' => $pagina,'languages' => $this->languages, 'templates' => $templates,'success' => $this->flash->getFirstMessage('success'), 'errors' => $this->flash->getFirstMessage('errors')]);
    	
}


  public function pagina_toevoegen(Request $request,Response $response) {	


   $templates =   $files = array_diff(scandir(__DIR__."/../../templates/frontend/"), array('.', '..'));

    return $this->view->render($response,'manager/manager-pagina-toevoegen.twig',['huidig' => 'manager-pagina-toevoegen','templates' => $templates, 'languages' => $this->languages]);
      }

public function manager_overview(Request $request,Response $response) {
	
	
$sql = $this->db->prepare("SELECT id,name,titel,description,keywords,language,DATE_FORMAT(datum,'%d-%m-%Y') as datum FROM `pages` ORDER BY id DESC LIMIT 50");
$sql->execute();
$paginas = $sql->fetchALL(PDO::FETCH_OBJ);
	
	
  return $this->view->render($response,"manager/manager-pagina-overview.twig",['paginas' => $paginas, 'huidig' => 'manager-pagina-overzicht','success' => $this->flash->getFirstMessage('success'), 'errors' => $this->flash->getFirstMessage('errors')]);
    	
     }
}
?>