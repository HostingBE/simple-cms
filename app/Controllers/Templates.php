<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;

class Templates {
	protected $view;
	protected $db;
	
	public function __construct(Twig $view, $db, $flash) {

	$this->view = $view;
	$this->db = $db;
	$this->flash = $flash;
	}
	
  public function post_edit(Request $request,Response $response) {
 
  $name = $request->getAttribute('file'); 	
  $data =  $request->getParsedBody(); 
	

$v = new Validator($data);

$v->rule('required','name');    


if (!$v->validate()) {
$first = key($v->errors());
$json = json_encode(array('status' => 'error','message' => $v->errors()[$first][0]));
$response->getBody()->write($json);   
return $response;
}  

  	  		
  if (fopen(__DIR__."/../../templates/frontend/".$name, "w") === false) {
  $response->getBody()->write(json_encode(array('status' => 'error','message' => 'unable to open file ' . $name . '!'))); 
  return $response;
  }
  
  $myfile = fopen(__DIR__."/../../templates/frontend/".$name, "w");
  fwrite($myfile, $data['template']);
  fclose($myfile);		


  $response->getBody()->write(json_encode(array('status' => 'success','message' => 'template with name ' . $name . ' succesfull edited !'))); 
  return $response;
  }
    
	public function post_add_template(Request $request,Response $response) {
    
	  $data =  $request->getParsedBody(); 

    $myfile = fopen(__DIR__."/../../templates/frontend/".$data['name'], "w") or die("Unable to open file!");
    fwrite($myfile, $data['template']);
    fclose($myfile);		
  
    $this->flash->addMessage('success','de template ' . $data['name'] .  ' is toegevoegd!');
    return $this->view->render($response,'backend/add_template.twig',['menu' => admin_menu(), 'template' => $template,'name' => $name ]);
    
    }		    

	public function add(Request $request,Response $response) {
    
 
    return $this->view->render($response,'manager/add-template.twig',['huidig' =>'manager-edit-template','template' => $template,'name' => $name,'errors' => $this->flash->getFirstMessage('errors'),'success' => $this->flash->getFirstMessage('success'),'status' => $this->flash->getFirstMessage('status')]);
    
    }		
	
	public function edit(Request $request,Response $response) {
    
    $name = $request->getAttribute('file');
    $templates = array_diff(scandir(__DIR__."/../../templates/frontend/"), array('.', '..','admin','errors'));   
   
    $myfile = fopen(__DIR__."/../../templates/frontend/".$name, "r") or die("Unable to open file!");
    $template = fread($myfile,filesize(__DIR__."/../../templates/frontend/".$name));
    fclose($myfile);	

  
    return $this->view->render($response,'manager/edit-template.twig',['huidig' => 'manager-edit-template', 'template' => $template,'name' => $name ]);
    
    }

    public function overview(Request $request,Response $response) {
 
       
    $datas = array_diff(scandir(__DIR__."/../../templates/frontend/"), array('.', '..','admin','errors'));   

    $i = 0;
    $templates = array();
    
    foreach ($datas as $data) {
    if (is_file(__DIR__."/../../templates/frontend/".$data)) {
    $size = filesize(__DIR__."/../../templates/frontend/".$data);
    $date = date("d F Y",filemtime(__DIR__."/../../templates/frontend/".$data));
    
    $templates[$i]['file'] = $data;
    $templates[$i]['size'] = $size;
    $templates[$i]['date'] = $date;
    $i++;
    }
  }   
  
   
     
    return $this->view->render($response,'manager/templates-overview.twig',['huidig' => 'manager-templates-overview', 'templates' => $templates ]);
    
    }
}
?>

