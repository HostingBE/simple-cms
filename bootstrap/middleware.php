<?php

use Slim\Middleware\ErrorMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Slim\Csrf\Guard;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use App\Middleware\LanguageLocal;


$errorMiddleware = new ErrorMiddleware(
  $app->getCallableResolver(),
  $app->getResponseFactory(),
  $container->get('settings')['displayErrorDetails'],
  $container->get('settings')['logErrorDetails'],
  $container->get('settings')['logErrorDetails'],
  $container->get('logger')
);

$app->add(new App\Middleware\VisitorInfo($container->get('db'), $container->get('logger')));
$app->add(new App\Middleware\CheckPartner($container->get('db'), $container->get('sitesettings'), $container->get('logger')));

$errorMiddleware->setErrorHandler(HttpForbiddenException::class, function ($request, $exception) use ($container) {
$response = new Response();
return $container->get('view')->render($response->withStatus(403), 'errors/403.twig',['message' => $exception->getMessage()]);  
});


$errorMiddleware->setErrorHandler(HttpNotFoundException::class, function ($request, $exception) use ($container) {
$response = new Response();
return $container->get('view')->render($response->withStatus(404), 'errors/404.twig',['message' => $exception->getMessage()]);	
});


$errorMiddleware->setErrorHandler(RuntimeException::class, function ($request, $exception) use ($container) {
$response = new Response();
return $container->get('view')->render($response->withStatus(500), 'errors/500.twig',['message' => $exception->getMessage(),'line' => $exception->getLine(),'code' => $exception->getCode(), 'file' => $exception->getFile()]); 
});

if ($container->get('settings')['translations']['enabled'] === true) {
$app->add(new LanguageLocal($app, $container->get('settings')['translations']['enabled'], $container->get('settings')['translations']['languages']));
}

$app->add($errorMiddleware);
?>