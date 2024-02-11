<?php

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;

class Category {
	protected $view;
	protected $db;
	
	public function __construct(Twig $view,$db) {

	  $this->view = $view;
	  $this->db = $db;
	  }


	  public function manager_verwijder(Request $request,Response $response) {
    
    
    $id = $request->getAttribute('id');
     
    $sql = $this->db->prepare("DELETE FROM categorie where id=:id");
    $sql->bindParam(':id',$id,PDO::PARAM_INT);
    $sql->execute();
    
    $response->getBody()->write("category deleted!");
    return $response;
    }		
    
         
    public function post_toevoegen(Request $request,Response $response) {

$data =  $request->getParsedBody();

$v = new Validator($data);

$v->rule('required','naam');    
$v->rule('required','soort');

 	if (!$v->validate()) {
$first = key($v->errors());
$json = json_encode(array('status' => 'error','message' => $v->errors()[$first][0]));
$response->getBody()->write($json);   
return $response;
}  
 	
  $sql = $this->db->prepare("SELECT count(id) AS total FROM categorie WHERE soort=:soort AND naam=:naam");
  $sql->bindparam(":naam", $data['naam'], PDO::PARAM_STR);
  $sql->bindparam(":soort", $data['soort'], PDO::PARAM_STR,1);
  $sql->execute();
  $aantal = $sql->fetch(PDO::FETCH_OBJ);

  if ($aantal->total != 0) {
  $response->getBody()->write(json_encode(array('status' => 'error','message' => 'categorie ' .$data['naam'] . ' bestaat al!'))); 
  return $response;
  }


 	$sql = $this->db->prepare("INSERT into categorie (naam,soort,date) values(:naam,:soort,now())");
    $sql->bindparam(":naam", $data['naam'], PDO::PARAM_STR);
    $sql->bindparam(":soort", $data['soort'], PDO::PARAM_STR,1);
    $sql->execute();

$response->getBody()->write(json_encode(array('status' => 'success','message' => 'categorie succesvol toegevoegd aan het systeem!'))); 
return $response;
}

public function manager_overview(Request $request,Response $response) {
  
    $sql = $this->db->prepare("SELECT id,naam,soort from categorie ORDER BY soort ASC");
    $sql->execute();
    $categories = $sql->fetchALL(PDO::FETCH_OBJ);
    
     
    return $this->view->render($response,'manager/categorie-overzicht.twig',['huidig' => 'manager-category-overview','categories' => $categories ]);
    
    }
}
?>


