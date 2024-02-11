<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Cartalyst\Sentinel\Native\Facades\Sentinel;


namespace App\Middleware;


class AuthMiddleware {
	
    public function __invoke(Request $request, RequestHandler $handler) {
        $response = $handler->handle($request);

        if (Sentinel::guest()) {
        die("redirect");	
        }
 
    return $response;
    }	
}

?>