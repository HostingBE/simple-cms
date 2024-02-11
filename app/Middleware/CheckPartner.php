<?php


namespace App\Middleware;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response as ResponseInterface;
use Cartalyst\Sentinel\Native\Facades\Sentinel;


class CheckPartner implements MiddlewareInterface {

protected $db;
protected $settings;
protected $logger;

public function __construct($db, $settings, $logger) {
    $this->db = $db;
    $this->settings = $settings;
    $this->logger = $logger;
}

public function process(Request $request, RequestHandler $handler): Response {

if (($request->getQueryParams()['utm_campaign'] == $this->settings['sitename']) && (strlen($request->getQueryParams()['utm_source']) == 32)) {

$sql = $this->db->prepare("SELECT user_id FROM activations WHERE code=:code LIMIT 1");
$sql->bindparam(":code",$request->getQueryParams()['utm_source'],PDO::PARAM_STR);
$sql->execute();
$user_data = $sql->fetch(PDO::FETCH_OBJ);

$referer = $_SERVER['HTTP_REFERER'] ?: 'onbekend';

$sql = $this->db->prepare("INSERT INTO partner(id,user,link,referal,ipadres,datum) VALUES('',:user,:link,:referal,:ipadres,now())");
$sql->bindparam(":user",$user_data->user_id,PDO::PARAM_INT);
$sql->bindparam(":link",parse_url($request->getUri())['path'],PDO::PARAM_STR);
$sql->bindparam(":referal",$referer,PDO::PARAM_STR);
$sql->bindparam(":ipadres",get_client_ip(),PDO::PARAM_STR);
$sql->execute();

$this->logger->info(get_class() . ": visitor via partner link " . $request->getQueryParams()['utm_source']);

}

$response = $handler->handle($request); 
return $response;
    }
}