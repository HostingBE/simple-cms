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


class Events {
	
protected $view;
protected $db;
protected $logger;
protected $settings;
protected $locale;
protected $translator;

public function __construct(Twig $view, $db, $logger, $settings, $locale, $translator) {
$this->view = $view;
$this->db = $db;
$this->logger = $logger;
$this->settings = $settings;
$this->locale = $locale;
$this->translator = $translator;
}

public function post(Request $request,Response $response) {
    $events = array();
    /*
	* CHAT events
	*/
    $sql = $this->db->prepare("SELECT id,ipaddress,message,DATE_FORMAT(date,'%H:%i') AS time FROM chat_messages WHERE date > date_sub(now(), interval 10 minute) LIMIT 5");
    $sql->execute();
    $chats = $sql->fetchALL(PDO::FETCH_OBJ);
    
    for ($i = 0; $i < count($chats);$i++) {
   
        if ($this->eventSeen('chat'.$chats[$i]->id) == true) {
                $events[] = array('heading' => "Chat", 'icon' => "info",'text' => $chats[$i]->time . ": " . $chats[$i]->message . " from ip-address " . $chats[$i]->ipaddress);
                }			
        }
    
    /*
	* INFORMATION events
	*/
    $sql = $this->db->prepare("SELECT id,name,company,email,substr(message,0,100) AS message, DATE_FORMAT(date,'%H:%i') AS time,ip FROM contact WHERE date > date_sub(now(), interval 10 minute) LIMIT 5");
    $sql->execute();
    $info = $sql->fetchALL(PDO::FETCH_OBJ);
    
    for ($i = 0; $i < count($info);$i++) {
         if ($this->eventSeen('contact'.$info[$i]->id) == true) {
                $events[] = array('heading' => "Contact request", 'icon' => "success",'text' => $info[$i]->time . ": " . $info[$i]->message . " from ip-address " . $info[$i]->ip . " with e-mail address " . $info[$i]->email);
    			}
    }
    /*
	* USER events
	*/
    $sql = $this->db->prepare("SELECT id,email,first_name,last_name,DATE_FORMAT(created_at,'%H:%i') AS tijd FROM users WHERE created_at > date_sub(now(), interval 10 minute) LIMIT 5");
    $sql->execute();
    $users = $sql->fetchALL(PDO::FETCH_OBJ);
    
    for ($i = 0; $i < count($users);$i++) {
         if ($this->eventSeen('user'.$users[$i]->id) == true) {
                $events[] = array('heading' => "New user", 'icon' => "danger",'text' => $users[$i]->tijd . ": " . $users[$i]->first_name . " " . $users[$i]->last_name . " has registered with email address " . $users[$i]->email);
    	}
    }
    $response->getBody()->write(json_encode(array('status' => 'success','message' => 'Events are spawned top the manager screen!','events' => $events)));	
     return  $response;
     }

private function eventSeen($event) {
        if (!is_array($_SESSION['events'])) {
            $_SESSION['events'] = array();
        }
        if (in_array($event, $_SESSION['events'])) {
            return false;
            } else {
            $_SESSION['events']+=array($event => $event);
            return true;
        }     
    }
}


?>