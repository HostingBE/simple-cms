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
use Valitron\Validator;


class Chat {
	
protected $view;
protected $db;
protected $flash;
protected $mail;
protected $logger;
protected $settings;
protected $locale;
protected $translator;

public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings, $locale, $translator) {
$this->view = $view;
$this->db = $db;
$this->flash = $flash;
$this->mail = $mail;
$this->logger = $logger;
$this->settings = $settings;
$this->locale = $locale;
$this->translator = $translator;
}


public function delete(Request $request,Response $response) {    

$id = $request->getattribute('id');
$session = $request->getattribute('session');

$sql = $this->db->prepare("SELECT id FROM chat WHERE session=:session AND id=:id");
$sql->bindparam(":session", $session, PDO::PARAM_STR);
$sql->bindparam(":id", $id, PDO::PARAM_INT);
$sql->execute();
$chat = $sql->fetch(PDO::FETCH_OBJ);

$sql = $this->db->prepare("DELETE FROM chat_messages WHERE chat=:id");
$sql->bindparam(":id", $chat->id, PDO::PARAM_INT);
$sql->execute();

$sql = $this->db->prepare("DELETE FROM chat WHERE id=:id AND session=:session"); 
$sql->bindparam(":session", $session, PDO::PARAM_STR);
$sql->bindparam(":id", $id, PDO::PARAM_INT);
$sql->execute();

 
return $response;
}


public function chat_check_login(Request $request,Response $response) {
    


    $sql = $this->db->prepare("SELECT count(*) as totaal FROM chat WHERE status='a' AND session=:session AND ipaddress=:ipaddress");
    $sql->bindparam(":ipaddress", get_client_ip(), PDO::PARAM_STR);
    $sql->bindparam(":session", session_id(), PDO::PARAM_STR);
    $sql->execute();
    $aantal = $sql->fetch(PDO::FETCH_OBJ);

    if ($aantal->totaal < 1) {

    $response->getBody()->write(json_encode(array('status' => 'error','message' => 'gebruiker met sessie ' . session_id() . ' is niet bekend of ingelogd!'))); 
    return  $response;  
    }   



    $response->getBody()->write(json_encode(array('status' => 'success','message' => 'gebruiker met sessie ' . session_id() . ' is reeds ingelogd!'))); 
    return  $response;  
    }   

public function manager_view(Request $request,Response $response) {

$id = $request->getattribute('id');
$session = $request->getattribute('session');
 
$manager_heading = $this->translator->get('chat_overview');

// bepalen of er al een chat sessie is opgestart voor deze gebruiker
$sql = $this->db->prepare("SELECT id,ipaddress,name,email,avatar,DATE_FORMAT(date,'%H:%i %d-%m-%Y') AS date FROM chat WHERE id=:id AND session=:session");
$sql->bindparam(":session", $session, PDO::PARAM_STR);
$sql->bindparam(":id", $id, PDO::PARAM_STR);
$sql->execute();
$chat = $sql->fetch(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT a.id,a.owner,a.message,DATE_FORMAT(a.date,'%H:%i') AS tijd FROM chat_messages AS a LEFT JOIN chat  AS b ON b.id=a.chat WHERE a.chat=:chat AND b.session=:session ORDER BY a.id DESC");
$sql->bindparam(":chat", $id, PDO::PARAM_INT);
$sql->bindparam(":session", $session, PDO::PARAM_STR);
$sql->execute(); 

$messages = $sql->fetchALL(PDO::FETCH_OBJ);

return $this->view->render($response,'manager/view-chat.twig',['huidig' => 'manager-chat-bekijken','manager_heading' => $manager_heading,'chat' => $chat, 'messages' => $messages, 'id' => $id,'session' => $session ]);
}    


public function post_manager_chat_message(Request $request,Response $response) {

    $data = $request->getParsedBody();
	
	$v = new Validator($data); 
    $v->rule('required','message');
    $v->rule('required','id');
    $v->rule('required','session');
    

	 if (!$v->validate()) {
        $this->flash->addMessage('errors',$v->errors());
        return $response->withHeader('Location','/manager/view-chat/'.$data['id'].'/'.$data['session'].'/');    
        }

    $owner = "beheerder";

    $sql = $this->db->prepare("INSERT INTO chat_messages (owner,ipaddress,chat,message,date) VALUES(:owner,:ipaddress,:chat,:message,now())");
    $sql->bindparam(":chat", $data['id'],PDO::PARAM_INT);
    $sql->bindparam(":owner", $owner,PDO::PARAM_STR);    	
    $sql->bindparam(":ipaddress", get_client_ip(),PDO::PARAM_STR);
    $sql->bindparam(":message",$data['message'],PDO::PARAM_STR);
    $sql->execute();

    $this->flash->addMessage('success','het bericht is geplaatst op de chat!');	
    $response = $response->withHeader('Location','/manager/view-chat/'.$data['id'].'/'.$data['session'].'/')->withStatus(302); 
    return $response;
}



public function post_add(Request $request,Response $response) {	
    $chat = array();

    $data = $request->getParsedBody();
	
	$v = new Validator($data); 
    $v->rule('required','message');

	 if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }	

    $sql = $this->db->prepare("SELECT id FROM chat WHERE ipaddress=:ipaddress AND session=:session");
    $sql->bindparam(":session", session_id(), PDO::PARAM_STR);
    $sql->bindparam(":ipaddress", get_client_ip(), PDO::PARAM_STR);
    $sql->execute();
    $chat = $sql->fetch(PDO::FETCH_OBJ);
    
    $owner = "visitor";

    $sql = $this->db->prepare("INSERT INTO chat_messages (owner,ipaddress,chat,message,date) VALUES(:owner,:ipaddress,:chat,:message,now())");
    $sql->bindparam(":chat", $chat->id,PDO::PARAM_INT);	
    $sql->bindparam(":owner", $owner, PDO::PARAM_STR);
    $sql->bindparam(":ipaddress", get_client_ip(),PDO::PARAM_STR);
    $sql->bindparam(":message",$data['message'],PDO::PARAM_STR);
    $sql->execute();

	$response->getBody()->write(json_encode(array('status' => 'success','message' => $this->translator->get('frontend.chat.message_added') . '!')));	
	return  $response;	
    }	

public function overview(Request $request,Response $response) {
   
    $manager_heading = ucfirst($this->translator->get('manager.chat.chat_overview'));

    if ($request->getQueryParams()) {
     	$id = $request->getQueryParams()['id'];
     	$session = $request->getQueryParams()['session'];
     	}


    $sql = $this->db->prepare("SELECT a.id,a.ipaddress,a.session,a.name,a.status,a.avatar,DATE_FORMAT(a.date,'%H:%i %d-%m-%Y') as datum,(SELECT count(id) AS aantal FROM `chat_messages` WHERE chat=a.id) as berichten FROM chat AS a ORDER BY a.id DESC LIMIT 20");
    $sql->execute();
    
    $chats = $sql->fetchALL(PDO::FETCH_OBJ);


return $this->view->render($response,'manager/chat-overview.twig',['huidig' => 'manager-chat-overview','chats' => $chats, 'manager_heading' => $manager_heading,'berichten' => $berichten, 'id' => $id,'session' => $session, 'errors' => $this->flash->getFirstMessage('errors'),'success' => $this->flash->getFirstMessage('success'),'info' => $this->flash->getFirstMessage('info')]);
}    



public function post_signin(Request $request,Response $response) {    
    
    $chat =  array();
    $data = $request->getParsedBody();
    
    $v = new Validator($data); 
    $v->rule('required','name');
    $v->rule('required','email');
    $v->rule('email','email');    
    $v->rule('required','message');
    $v->rule('required','antispam');

     if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }   

      $this->logger->info("CHAT: anti spam precaution form " . $data['antispam'] .  " calculated " . base64_encode(parse_url($_SERVER['HTTP_REFERER'])['path']) . $_SERVER['HTTP_REFERER']);

      if ($data['antispam'] != base64_encode(parse_url($_SERVER['HTTP_REFERER'])['path'])) {
      $this->logger->info("CHAT: anti spam precaution form " . $data['antispam'] .  " calculated " . base64_encode(parse_url($_SERVER['HTTP_REFERER'])['path']) . " is NOT correct!");  
      $response->getBody()->write(json_encode(array('status' => 'error','message' => '[2] no valid anti spam precautions are taken!')));  
      return $response; 
      } 

    $status = 'a';
    $eigenaar = "visitor";
    $avatar = '/images/default-icon.jpg';

    // bepalen of er al een chat sessie is opgestart voor deze gebruiker
    $sql = $this->db->prepare("SELECT id FROM chat WHERE ipaddress=:ipaddress AND session=:session");
    $sql->bindparam(":session", session_id(), PDO::PARAM_STR);
    $sql->bindparam(":ipaddress", get_client_ip(), PDO::PARAM_STR);
    $sql->execute();
    $chat = $sql->fetch();
    
    if (!isset($chat['id'])) {
    $sql = $this->db->prepare("INSERT INTO chat (session,ipaddress,name,email,status,avatar,date) VALUES(:session,:ipaddress,:name,:email,:status,:avatar,now())");
    $sql->bindparam(":session", session_id(),PDO::PARAM_STR);
    $sql->bindparam(":name", $data['name'],PDO::PARAM_STR);
    $sql->bindparam(":email", $data['email'],PDO::PARAM_STR);   
    $sql->bindparam(":ipaddress", get_client_ip(),PDO::PARAM_STR);
    $sql->bindparam(":status", $status,PDO::PARAM_STR);
    $sql->bindparam(":avatar", $avatar,PDO::PARAM_STR);
    $sql->execute();
    $chat['id'] = $this->db->lastinsertid();
    } 

    $sql = $this->db->prepare("INSERT INTO chat_messages (owner,ipaddress,chat,message,date) VALUES(:owner,:ipaddress,:chat,:message,now())");
    $sql->bindparam(":chat", $chat['id'],PDO::PARAM_INT); 
    $sql->bindparam(":owner", $eigenaar, PDO::PARAM_STR);
    $sql->bindparam(":ipaddress", get_client_ip(),PDO::PARAM_STR);
    $sql->bindparam(":message",$data['message'],PDO::PARAM_STR);
    $sql->execute();


    /*
    * Hier handelen we automatisch berichten af
    */
    $open = date('Y-m-d') . " " . '06:00:00';
    $dicht = date('Y-m-d') . " " . '16:00:00';
    $open = strtotime($open);
    $dicht = strtotime($dicht);


    if(time() < $open || time() > $dicht) {
    $eigenaar = "beheerder";
    $buitenkantoortijden = "Momenteel zijn we niet aanwezig, we zijn bereikbaar op werkdagen van 09:00 tot 18:00. Je chat wordt wel gelezen tijdens kantoortijden en beantwoord via email!";
    $this->logger->warning("Chat aanvraag automatisch beantwoord we zijn buiten kantoor tijden!");
    $sql = $this->db->prepare("INSERT INTO `chat_messages` (id,eigenaar,ipaddress,chat,message,date) VALUES('',:eigenaar,:ipaddress,:chat,:message,now())");


    $sql->bindparam(":chat", $chat['id'],PDO::PARAM_INT); 
    $sql->bindparam(":owner", $owner, PDO::PARAM_STR);
    $sql->bindparam(":ipaddress", get_client_ip(),PDO::PARAM_STR);
    $sql->bindparam(":message",$buitenkantoortijden,PDO::PARAM_STR);
    $sql->execute();
    }

    $response->getBody()->write(json_encode(array('status' => 'success','message' => 'chat user succesfully signed-in!'))); 
    return  $response;  
    }   

public function chat_overview(Request $request,Response $response) {	


    $sql = $this->db->prepare("SELECT id,name,avatar FROM chat WHERE ipaddress=:ipaddress AND session=:session");
    $sql->bindparam(":session", session_id(), PDO::PARAM_STR);
    $sql->bindparam(":ipaddress", get_client_ip(), PDO::PARAM_STR);
    $sql->execute();
    $chat = $sql->fetch(PDO::FETCH_OBJ); 

    if (!is_numeric($chat->id)) {
	$response->getBody()->write(json_encode(array('status' => 'error','message' => 'Welcome to '. $this->settings['sitename'] .', how can i help you?')));	
	return  $response;	
    }

    $sql = $this->db->prepare("SELECT id, owner, message,DATE_FORMAT(date,'%H:%i') AS time FROM chat_messages WHERE chat=:chat AND ipaddress=:ipaddress");
    $sql->bindparam(":chat", $chat->id, PDO::PARAM_INT);
    $sql->bindparam(":ipaddress", get_client_ip(), PDO::PARAM_STR);
    $sql->execute(); 
    $messages = $sql->fetchALL(PDO::FETCH_OBJ);
    
    for ($i = 0;$i < count($messages);$i++) {
        
        if ($messages[$i]->owner == "beheerder") {
        $messages[$i]->name = "Team SeoSite";
        $messages[$i]->avatar = "/images/default-icon.jpg";
        $messages[$i]->align = "right";
        $messages[$i]->text = "light";   
        $messages[$i]->background = "secondary";    
        }

        if ($messages[$i]->owner == "visitor") {
        $messages[$i]->name = ucfirst($chat->name);
        $messages[$i]->avatar = $chat->avatar;
        $messages[$i]->align = "left";       
        $messages[$i]->text = "light";   
        $messages[$i]->background = "primary";           
        }   
    }

    $response->getBody()->write(json_encode(array('status' => 'success','message' => 'chat berichten opgehaald!', 'chat' => $chat,'messages' => $messages)));	
	return  $response;	
}

}


?>