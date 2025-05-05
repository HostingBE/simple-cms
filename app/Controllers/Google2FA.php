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
use Cartalyst\Sentinel\Native\Facades\Sentinel as Sentinel;
use PragmaRX\Google2FA\Support\Constants;

class Google2FA {

protected $view;
protected $db;
protected $flash;
protected $logger;
protected $settings;
protected $translator;
protected $locale;
protected $user;

public function __construct(Twig $view, PDO $db, $flash, $logger, $settings, $locale, $translator) {
    $this->view = $view;
    $this->db = $db;
    $this->flash = $flash;
    $this->logger = $logger;
    $this->settings = $settings;
    $this->translator = $translator;
    $this->locale = $locale;
    $this->user = Sentinel::getUser();
}

public function verify_code(Request $request, Response $response) {

$data = $request->getParsedBody();

$v = new Validator($data);
$v->rule('required','code2fa');
$v->rule('length','code2fa','6');
$v->rule('numeric','code2fa');

if (!$v->validate()) {
    $this->flash->addMessage('errors',$v->errors());
    return $response->withHeader('Location','/2factor-auth')->withStatus(302);
    }


$sql = $this->db->prepare("SELECT a.secret FROM settings AS a LEFT JOIN users AS b ON b.id=a.user WHERE a.user=:user AND b.twofactor='y' LIMIT 1");
$sql->bindparam(":user",$this->user->id,PDO::PARAM_INT);
$sql->execute();
$user2fa = $sql->fetch(PDO::FETCH_OBJ);


$google2fa = new \PragmaRX\Google2FA\Google2FA();
if ($google2fa->verifyKey($user2fa->secret, $data['code2fa'])) {

$this->logger->warning(get_class() . ': user ' . $user->email .  ' succesful authenticated in the authenticator app!');

$_SESSION['twofactor'] = 'true'; 

if ($this->settings['redirect']) {
    return $response->withHeader('Location',$this->settings['redirect'])->withStatus(301); 
    } else {
    return $response->withHeader('Location',$returnurl . '/dashboard')->withStatus(301); 
           }
} else {
 $this->logger->error(get_class() . ': user ' . $user->email .  ' failed to authenticate via the authenticator app!');
 $this->flash->addMessage('errors',"invalid authentication code supplied!"); 
 return $response->withHeader('Location','/2factor-auth')->withStatus(302);  

}        

 $this->flash->addMessage('errors',"invalid 2 factor authentication transaction contact support!"); 
 return $response->withHeader('Location','/2factor-auth')->withStatus(302);  
}   


public function login(Request $request, Response $response) {

return $this->view->render($response,'frontend/google2fa-login.twig',['huidig' => 'google2fa-login','errors' => $this->flash->getFirstMessage('errors'),'success' => $this->flash->getFirstMessage('success'),'status' => $this->flash->getFirstMessage('status')]);
}

public function verify(Request $request, Response $response) {


$data = $request->getParsedBody();

$v = new Validator($data);
$v->rule('required','code2fa');
$v->rule('length','code2fa','6');
$v->rule('numeric','code2fa');

if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
    } 

$sql = $this->db->prepare("SELECT a.secret FROM settings AS a LEFT JOIN users AS b ON b.id=a.user WHERE a.user=:user AND b.twofactor='n' LIMIT 1");
$sql->bindparam(":user",$this->user->id,PDO::PARAM_INT);
$sql->execute();
$user2fa = $sql->fetch(PDO::FETCH_OBJ);

if ($sql->rowCount() == 0) {
$response->getBody()->write(json_encode(array('status' => 'error','message' => '2FA setup FAILED, could not retrieve user information contact support!')));
return $response;   
}

$google2fa = new \PragmaRX\Google2FA\Google2FA();
if ($google2fa->verifyKey($user2fa->secret, $data['code2fa'])) {
$respupd = Sentinel::update($this->user, ['twofactor' => 'y']);
} else {
$response->getBody()->write(json_encode(array('status' => 'error','message' => '2FA setup FAILED, 6 digit code does not match!')));
return $response;
}


$response->getBody()->write(json_encode(array('status' => 'success','message' => '2FA setup complete you have secured your account!')));
return $response;
}

public function overview(Request $request, Response $response) {

$twofa = 'n';

if ($this->user->twofactor == 'n') {

$google2fa = (new \PragmaRX\Google2FAQRCode\Google2FA());

$google2fa->setAlgorithm(Constants::SHA512);

$secret = $google2fa->generateSecretKey(32);

$image_url = $google2fa->getQRCodeInline(
 $this->settings['sitename'],
 $this->user->email,
 $secret
        );
} else {
$twofa = 'y';
}

$sql = $this->db->prepare("SELECT count(id) AS aantal FROM settings WHERE user=:user");
$sql->bindparam(":user",$this->user->id, PDO::PARAM_INT);
$sql->execute();

$aantal = $sql->fetch(PDO::FETCH_OBJ);

if ($aantal->aantal == 0) {
$sql = $this->db->prepare("INSERT INTO settings (user,email,language,forum_name,secret) VALUES(:user,:email,:language,:forum_name,:secret)");
$sql->bindparam(":user",$this->user->id, PDO::PARAM_INT);
$sql->bindparam(":email",$this->user->email, PDO::PARAM_STR);
$sql->bindparam(":language",$this->locale, PDO::PARAM_STR);
$sql->bindparam(":forum_name",$this->user->first_name, PDO::PARAM_STR);
$sql->bindparam(":secret",$secret, PDO::PARAM_STR);
$sql->execute();
} 

if (($twofa == 'n') && ($aantal->aantal != 0)) {
$sql = $this->db->prepare("UPDATE settings SET secret=:secret WHERE user=:user");
$sql->bindparam(":user",$this->user->id, PDO::PARAM_INT);
$sql->bindparam(":secret",$secret, PDO::PARAM_STR);
$sql->execute();
}


return $this->view->render($response,'frontend/google2fa.twig',['current' =>  substr($request->getUri()->getPath(),1),'image_url' => $image_url,'twofa' => $twofa,'errors' => $this->flash->getFirstMessage('errors'),'success' => $this->flash->getFirstMessage('success'),'status' => $this->flash->getFirstMessage('status') ]);
    	} 
}

?>