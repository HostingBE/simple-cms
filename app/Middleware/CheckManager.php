<?php


namespace App\Middleware;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response as ResponseInterface;
use Cartalyst\Sentinel\Native\Facades\Sentinel;


class CheckManager implements MiddlewareInterface {

public function process(Request $request, RequestHandler $handler): Response {

if (!$user = Sentinel::check()) {
    $response = new ResponseInterface();
    return $response->withHeader('Location','/login')->withStatus(302);
    }
    $user = Sentinel::getUser();
if (!$user->inRole('administrator')) {
    throw new \Slim\Exception\HttpForbiddenException($request);
    }

$response = $handler->handle($request); 
return $response;
    }
}
?>