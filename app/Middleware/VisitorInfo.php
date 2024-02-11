<?php

namespace App\Middleware;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Detection\MobileDetect;

class VisitorInfo implements MiddlewareInterface {

protected $db;
protected $logger;
protected $debug = false;

public function __construct($db,$logger) {
	$this->db = $db;
	$this->logger = $logger;
	}

public function process(Request $request, RequestHandler $handler): Response {

$response = $handler->handle($request);  

if (filter_var($_SESSION['info']->ip, FILTER_VALIDATE_IP)) { 
	return $response;
	}

$sql = $this->db->prepare("SELECT id,ip,hostname,city,region,country,loc,org,postal,timezone FROM ipinfo WHERE ip=:ip");
$sql->bindparam(":ip",$this->get_client_ip(),PDO::PARAM_STR);
$sql->execute();
$info = $sql->fetch(PDO::FETCH_OBJ);

/*
* insert ipinfo in the database
*/

if (!filter_var($info->ip, FILTER_VALIDATE_IP)) { 

$info = json_decode($this->get_ip_info($this->get_client_ip()));

# internal network address continue
if ($info->bogon == 1) {

return $response;
}

if (!filter_var($info->ip, FILTER_VALIDATE_IP)) { 
return $response;
}

$sql = $this->db->prepare("INSERT INTO ipinfo(ip,hostname,city,region,country,loc,org,postal,timezone,date) VALUES(:ip,:hostname,:city,:region,:country,:loc,:org,:postal,:timezone,now())");
$sql->bindparam(":ip",$this->get_client_ip(),PDO::PARAM_STR);
$sql->bindparam(":hostname",substr($info->hostname,0,63),PDO::PARAM_STR);
$sql->bindparam(":city",$info->city,PDO::PARAM_STR);
$sql->bindparam(":region",$info->region,PDO::PARAM_STR);
$sql->bindparam(":country",$info->country,PDO::PARAM_STR);
$sql->bindparam(":loc",$info->loc,PDO::PARAM_STR);
$sql->bindparam(":org",substr($info->org,0,63),PDO::PARAM_STR);
$sql->bindparam(":postal",$info->postal,PDO::PARAM_STR);
$sql->bindparam(":timezone",$info->timezone,PDO::PARAM_STR);
$sql->execute();
  }
$_SESSION['info'] = $info;

$detect = new MobileDetect;

$_SESSION['info']->location = strtolower($info->country) ?: 'us';
$_SESSION['info']->language = $this->get_language();
$_SESSION['info']->useragent = $detect->getUserAgent();


if( !$detect->isMobile() && !$detect->isTablet()) {
    $_SESSION['info']->apparaat = "computer";
    } 
if($detect->isMobile()) { $_SESSION['info']->apparaat = "mobiel"; }
if($detect->isTablet()) { $_SESSION['info']->apparaat = "tablet"; }  

$detect = "";

if ($this->debug === true) {
    $this->logger->info(get_class() . " info opgehaald voor gebruiker middleware uitgevoerd",['IP-address' => $this->get_client_ip(),'info' => $info]);
    }
return $response;
}

/*
* ophalen van de clientinfo
*/
protected function get_ip_info($ip) {
	$json = file_get_contents("https://ipinfo.io/".$ip."?token=705a2ccd553b48");
	return $json;
	}
/*
* bepalen van de taal vanuit de browser
*/
protected function get_language() {
	$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	$acceptLang = ['fr', 'it', 'en', 'nl', 'de', 'be']; 
	$lang = in_array($lang, $acceptLang) ? $lang : 'en';
return $lang;    
}

protected function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }

    return $ipaddress;
    }

}
?>