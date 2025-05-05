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
use Cartalyst\Sentinel\Native\Facades\Sentinel;


class Todo {
	
protected $view;
protected $db;
protected $flash;
protected $mail;
protected $logger;
protected $settings;

public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings) {
$this->view = $view;
$this->db = $db;
$this->flash = $flash;
$this->mail = $mail;
$this->logger = $logger;
$this->settings = $settings;
}

public function verwijder(Request $request,Response $response) {

         $id = $request->getAttribute('id');
          $user = Sentinel::getUser();

        $sql = $this->db->prepare("DELETE FROM todo WHERE id=:id and user=:user");
        $sql->bindparam(":id",$id,PDO::PARAM_INT);
        $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
        $sql->execute();

        $this->flash->addMessage('success',"het todo is verwijderd van de lijst!");
        return $response->withHeader('Location','/manager/todo-overview')->withStatus(302);  
        }

public function post_bewerken(Request $request,Response $response) {
	      
     $id = $request->getAttribute('id');
	  $data = $request->getParsedBody();
	 
	 $v = new Validator($data); 
   $v->rule('required','ap-categorie');
    $v->rule('required','ap-todo');
    $v->rule('required','ap-status');

	 if (!$v->validate()) {
        $this->flash->addMessage('errors',$v->errors());
        return $response->withHeader('Location','/manager/todo-bewerken/'.$id.'/')->withStatus(302);  
        }	
 
     	 $user = Sentinel::getUser(); 
     	 
     	 
     	  $sql = $this->db->prepare("UPDATE todo set categorie=:categorie,todo=:todo,status=:status where id=:id");
        $sql->bindparam(":id",$id,PDO::PARAM_INT);
        $sql->bindparam(":categorie",$data['ap-categorie'],PDO::PARAM_STR);
        $sql->bindparam(":todo",$data['ap-todo'],PDO::PARAM_STR);
        $sql->bindparam(":status",$data['ap-status'],PDO::PARAM_STR,1);
        $sql->execute();    	 
     	 
     	  $this->flash->addMessage('success',"todo succesvol bijgewerkt in de database!");
        return $response->withHeader('Location','/manager/todo-bewerken/'.$id.'/')->withStatus(302);  
        }	

public function post_toevoegen(Request $request,Response $response) {
	      

	  $data = $request->getParsedBody();
	 
	 $v = new Validator($data); 
   $v->rule('required','ap-categorie');
    $v->rule('required','ap-todo');
    $v->rule('required','ap-status');

	 if (!$v->validate()) {
        $this->flash->addMessage('errors',$v->errors());
        return $response->withHeader('Location','/manager/todo-overview')->withStatus(302);  
        }	
 
     	 $user = Sentinel::getUser();    
       
     $sql = $this->db->prepare("INSERT INTO todo (user,categorie,todo,status,datum) VALUES(:user,:categorie,:todo,:status,now())");
     $sql->bindparam(":user",$user->id,PDO::PARAM_INT);
     $sql->bindparam(":categorie",$data['ap-categorie'],PDO::PARAM_STR);
     $sql->bindparam(":todo",$data['ap-todo'],PDO::PARAM_STR);
     $sql->bindparam(":status",$data['ap-status'],PDO::PARAM_STR,1);
     $sql->execute();
     

	
        $this->flash->addMessage('success',"todo toegevoegd aan de database!");
        return $response->withHeader('Location','/manager/todo-overview')->withStatus(302);  
        }	

public function bewerken(Request $request,Response $response) {
	             
	             $id = $request->getAttribute('id');
	
		      $soort = "t";
	 
	      $sql = $this->db->prepare("SELECT id,naam FROM categorie where soort=:soort");
	      $sql->bindparam(":soort",$soort,PDO::PARAM_STR,1);   
	      $sql->execute();
	      $categorie = $sql->fetchALL(PDO::FETCH_OBJ);	
	             
	      $sql = $this->db->prepare("SELECT id,categorie,todo,status,datum FROM todo where id=:id");
	      $sql->bindparam(":id",$id,PDO::PARAM_INT);
	      $sql->execute();
	      $todo = $sql->fetch(PDO::FETCH_OBJ);
	
	      return $this->view->render($response,'manager/todo-edit.twig',['meta' =>  $meta,'huidig' => 'todo-bewerken','todo' => $todo,'categorie' => $categorie,'success' => $this->flash->getFirstMessage('success'), 'errors' => $this->flash->getFirstMessage('errors')]);
      }

public function overview(Request $request,Response $response) {

		      $soort = "t";
	 
	      $sql = $this->db->prepare("SELECT id,naam FROM categorie where soort=:soort");
	      $sql->bindparam(":soort",$soort,PDO::PARAM_STR,1);   
	      $sql->execute();
	      $categorie = $sql->fetchALL(PDO::FETCH_OBJ);	 
	      
	      $sql = $this->db->prepare("SELECT a.id,b.naam as categorie_naam,a.todo,a.status,a.datum FROM todo AS a LEFT JOIN categorie  AS b ON b.id=a.categorie WHERE a.status != 'c'");
	      $sql->execute();
	      $todos = $sql->fetchALL(PDO::FETCH_OBJ);
	
	      return $this->view->render($response,'manager/todo-overview.twig',['meta' =>  $meta,'huidig' => 'todo-overview','todos' => $todos,'categorie' => $categorie,'success' => $this->flash->getFirstMessage('success'), 'errors' => $this->flash->getFirstMessage('errors')]);
      }
}


?>