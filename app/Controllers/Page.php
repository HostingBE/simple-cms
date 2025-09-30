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
use Slim\Exception\HttpNotFoundException;
use Valitron\Validator;

class Page  {
	protected $view;
	protected $db;
	protected $flash;
    protected $logger;
    protected $locale;
	protected $translator;
    
	public function __construct(Twig $view, $db, $flash, $logger, $locale, $translator) {

	$this->view = $view;
	$this->db = $db;
	$this->flash = $flash;
    $this->logger = $logger;
	$this->locale = $locale;
    $this->translator = $translator;
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

    if (file_exists(__DIR__ . '/../../.new_install')) {
    $run = new \App\Controllers\FirstRun($this->view, $this->db, $this->logger);
    $run->install();
    }
       
    $sql = $this->db->prepare("SELECT id,name,titel,description,keywords,content,template FROM pages WHERE name=:slug AND language=:language AND publish='y' LIMIT 1");
    $sql->bindParam(':slug',$page,PDO::PARAM_STR);
    $sql->bindParam(':language',$this->locale, PDO::PARAM_STR, 2);   
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
