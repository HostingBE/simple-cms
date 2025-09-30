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

if ((isset($request->getQueryParams()['utm_campaign'])) && ($request->getQueryParams()['utm_campaign'] == $this->settings['sitename']) && (strlen($request->getQueryParams()['utm_source']) == 32)) {

$sql = $this->db->prepare("SELECT user_id FROM activations WHERE code=:code LIMIT 1");
$sql->bindparam(":code",$request->getQueryParams()['utm_source'],PDO::PARAM_STR);
$sql->execute();
$user_data = $sql->fetch(PDO::FETCH_OBJ);

$referer = $_SERVER['HTTP_REFERER'] ?: 'onbekend';

$sql = $this->db->prepare("INSERT INTO partner(id, user, link, referal,ipadres, datum) VALUES('',:user,:link,:referal,:ipadres,now())");
$sql->bindparam(":user",$user_data->user_id,PDO::PARAM_INT);
$sql->bindparam(":link",parse_url($request->getUri())['path'],PDO::PARAM_STR);
$sql->bindparam(":referal",$referer,PDO::PARAM_STR);
$sql->bindparam(":ipadres",(new \App\Helpers\Helpers)->get_client_ip(), PDO::PARAM_STR);
$sql->execute();

$this->logger->info(__CLASS__ . ": visitor via partner link " . $request->getQueryParams()['utm_source']);

}

$response = $handler->handle($request); 
return $response;
    }
}