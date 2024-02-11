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
use Slim\Exception\HttpNotFoundException;
use Valitron\Validator;

class Page  {
	protected $view;
	protected $db;
	protected $flash;
	
	public function __construct(Twig $view,$db,$flash,$locale,$default) {

	$this->view = $view;
	$this->db = $db;
	$this->flash = $flash;
	$this->locale = $locale ?: $default;
	}


	public function status(Request $request,Response $response) {

    $freediskspace = disk_free_space(__DIR__)/1024;
    $diskspace = disk_total_space(__DIR__)/1024;
    $free = $diskspace - $freediskspace;
    $freeperc = round($diskspace / $free,2);

    $sql = $this->db->prepare("SELECT count(id) AS aantal FROM activations");
    $sql->execute();
    $aantal = $sql->fetch(PDO::FETCH_OBJ);


    return $this->view->render($response,'/frontend/status.twig',['aantal' => $aantal, 'free' => $freeperc])->withHeader('Content-Type', 'text/plain')->withStatus(200);
    } 
   
    public function show(Request $request,Response $response) {
	
	$page = $request->getAttribute('page');

	  if ($page == "") {
	  	 $page = "index";
	  	 }

       
    $sql = $this->db->prepare("SELECT id,name,titel,description,keywords,content,template FROM pages WHERE name=:slug AND language=:language AND publish='y' LIMIT 1");
    $sql->bindParam(':slug',$page,PDO::PARAM_STR);
    $sql->bindParam(':language',$this->locale,PDO::PARAM_STR,2);   
    $sql->execute();
    $pageobj = $sql->fetch(PDO::FETCH_OBJ);

    if (!is_object($pageobj)) {
    throw new HttpNotFoundException($request);
    }
    
    $meta['title'] = $pageobj->titel;
    $meta['description'] = $pageobj->description;
    $meta['keywords'] = $pageobj->keywords;
    $meta['url'] = parse_url($request->getUri())['path'];
        
    return $this->view->render($response,'/frontend/'.$pageobj->template,['page' => $pageobj, 'huidig' => $page, 'meta' => $meta,'errors' => $this->flash->getFirstMessage('errors'),'success' => $this->flash->getFirstMessage('success'),'status' => $this->flash->getFirstMessage('status')]);
    
    }
   
   
}
?>
