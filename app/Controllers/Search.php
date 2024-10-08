<?php

/**
 * @author Constan van Suchtelen van de Haere <constan@hostingbe.com>
 * @copyright 2023 HostingBE
 */

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use JasonGrimes\Paginator;
use Valitron\Validator;
use AlesZatloukal\GoogleSearchApi\GoogleSearchApi;

class Search {

const API_URL = 'https://api.hostingbe.nl';
const API_PATH = '/api/';

protected $view;
protected $db;
protected $flash;
protected $mail;
protected $logger;
protected $settings;
protected $locale;
protected $translator;


public function __construct(Twig $view,$db,$flash,$mail,$logger,$settings,$locale,$translator) {
    $this->view = $view;
    $this->db = $db;
    $this->flash = $flash;
    $this->mail = $mail;
    $this->logger = $logger;
    $this->settings = $settings;
    $this->locale = $locale;
    $this->translator = $translator;
    }

public function search(Request $request, Response $response) {
$start = 0;
$page = "1";

if ((!$this->settings['apiusername']) || (!$this->settings['apiusername'])) {
    $response->getBody()->write(json_encode(array('status' => 'error','message' => "you do have to enter a API username and password"))); 
    return  $response;
    }

 if ($request->getMethod() == "GET") {
          $data['q'] = $request->getAttribute('q');   
        if ($request->getQueryParams()) {
        $page = $request->getQueryParams()['page'];
        }
    }

    if ($request->getMethod() == "POST") {
        $data = $request->getParsedBody();
    }

  $v = new Validator($data); 
  $v->rule('required','q');

  if (!$v->validate()) {
        $errormessage = current((Array)$v->errors())[0];
        $response->getBody()->write(json_encode(array('status' => 'error','message' => $errormessage))); 
        return  $response;
        }

        /*
        * store data in site_search_history
        */
 if ($request->getMethod() == "POST") {
        $sql = $this->db->prepare("INSERT INTO site_search_history (query,ip,referer,date) VALUES(:query,:ip,:referer,now())");
        $sql->bindparam(":query",$data['q'],PDO::PARAM_STR);
        $sql->bindparam(":ip",get_client_ip(),PDO::PARAM_STR);
        $sql->bindparam(":referer",$_SERVER['HTTP_REFERER'],PDO::PARAM_STR);
        $sql->execute();
}

$client = new \App\Search\SiteSearch([
'url' => $this::API_URL,
'path' => $this::API_PATH
]);

$website = $_SERVER['SERVER_NAME'];
$ip = get_client_ip();
$referer = $_SERVER['HTTP_REFERER'] ?: null;

if ($client->loggedin === false) {
$res = $client->login($this->settings['apiusername'],$this->settings['apipassword']);
}

$res = $client->search($data['q'],$website,$ip,$referer);
if ($res->code != "200") {
      return $this->view->render($response,'frontend/error.twig',['message' => $res->code . " " . $res->message]);
} 

// aantal pagina's bepalen
$start = $page * $this->settings['records'] - $this->settings['records']; 
    
if ($request->getMethod() == "POST") {
    $url = (string) parse_url($request->getUri())['path']  ."/". str_replace(' ','-', $data['q']) . "/?page=(:num)"; 
    }
    if ($request->getMethod() == "GET") {
    $url = (string) parse_url($request->getUri())['path']  . "?page=(:num)"; 
    }

    $pagelinks = new Paginator($res->data->hits, $this->settings['records'], $page ,  $url);
    $pagelinks->setMaxPagesToShow(5);
    $pagelinks->setPreviousText('previous');
    $pagelinks->setNextText('next');

$this->logger->info("Search: Searched for " . $data['q'] . " which returned " . $res->data->hits .  " results!",array('ipaddress' => get_client_ip()));


$meta['title'] = $this->translator->get('search.search_results',['q' => $data['q']]);
$meta['description'] = $this->translator->get('search.description',['q' => $data['q'],'aantal' => $res->data->hits,'page' => $page]);  
$meta['keywords']= $this->translator->get('search.keywords');

$results = [];

if (is_array($res->data->results)) {
$results = array_slice($res->data->results,$start,$this->settings['records']);
}

return $this->view->render($response,'frontend/website-search.twig',['huidig' => 'website-search','q'=> $data['q'],'results' => $results,'total' => $res->data->hits,'execution_time' => $res->data->execution_time,'meta' => $meta, 'paginator' => $pagelinks,'url' => $this->settings['url'],'start' => $start]);
      } 


public function overview(Request $request, Response $response) {

return $this->view->render($response,'backend/search-overview.twig',['huidig' => 'search-overview']);
      }   
}

?>