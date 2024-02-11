<?php


namespace App\Middleware;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response as ResponseInterface;
use Cartalyst\Sentinel\Native\Facades\Sentinel;


class CheckUser implements MiddlewareInterface {

public function process(Request $request, RequestHandler $handler): Response {

if (!$user = Sentinel::check()) {
	$response = new ResponseInterface();
    return $response->withHeader('Location','/login')->withStatus(302);
    }

if (!is_object($user)) {
	$response = new ResponseInterface();
    return $response->withHeader('Location','/login?nouser')->withStatus(302);
}

$response = $handler->handle($request); 
return $response;
    }
}
?>