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


class keywords {

protected $view;
protected $db;
protected $flash;
protected $logger;
protected $mail;
protected $settings;
protected $locale;
protected $languages;

public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings, $locale, $languages) {
$this->view = $view;
$this->db = $db; 
$this->flash = $flash;
$this->mail = $mail;       
$this->logger = $logger;
$this->settings = $settings;
$this->locale = $locale;
$this->languages = $languages;
}


public function delete(Request $request, Response $response) {

$id = $request->getAttribute('id');
$code = $request->getAttribute('code');

$sql = $this->db->prepare("DELETE FROM keywords WHERE id=:id AND code=:code");
$sql->bindParam(':code',$code,PDO::PARAM_STR,32); 
$sql->bindParam(':id',$id,PDO::PARAM_INT); 
$sql->execute();

$response->getBody()->write(json_encode(array('status' => 'success','message' => 'keyword succesfull deleted!')));
return $response;
}

public function post_add(Request $request, Response $response) {

$data =  $request->getParsedBody();
$v = new Validator($data);

$v->rule('required',array('keyword','link','language'));  

       if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }  
$code = (new \App\Helpers\Helpers)->RandomString(32);

$sql = $this->db->prepare("INSERT INTO keywords(code,keyword,link,language,date) VALUES(:code,:keyword,:link,:language,now())");
$sql->bindParam(':code',$code,PDO::PARAM_STR,32); 
$sql->bindParam(':keyword',$data['keyword'],PDO::PARAM_STR); 
$sql->bindParam(':link',$data['link'],PDO::PARAM_STR);
$sql->bindParam(':language',$data['language'],PDO::PARAM_STR,2); 
$sql->execute();

$response->getBody()->write(json_encode(array('status' => 'success','message' => 'keyword added to your website!'))); 
return  $response;
}


public function overview(Request $request, Response $response) {


$sql = $this->db->prepare("SELECT id, code, keyword, link, language FROM keywords");
$sql->execute();
$keywords = $sql->fetchALL(PDO::FETCH_OBJ);


return $this->view->render($response,'manager/keywords-overview.twig',['current' =>  str_replace('/', '-', substr($request->getUri()->getPath(),1)),'meta' => $meta, 'keywords' => $keywords, 'languages' => array_column($this->languages,'language')]);

}

}
?>

