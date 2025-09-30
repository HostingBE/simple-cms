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
namespace App\Search;

use GuzzleHttp\Client;

/**
 * PHP class SiteSearch to connect to the API of HostingBE
 */

class SiteSearch {
	
protected $url;
protected $path = '/api';
protected $settings;
protected $client;
protected $token;
public $loggedin = false;


public function __construct(array $settings) {
	 $this->settings = $settings;
    $this->client = new Client(['base_uri' => $this->settings['url'] . $this->settings['path'],'timeout'  => 2.0,'http_errors' => false]);
    }
/**
 *  execute the ping command
 */
public function ping() {
$res = $this->client->request('GET','ping');	
return $this->handlereply($res);
}
/**
 *  execute the login command with username and password
 */
public function login($username, $password) {
$res = $this->client->request('POST','login',['form_params' => ['username' => $username, 'password' => $password ]]);
$body = $res->getBody();
$data = $this->output($body->getContents());

if (($data->code == 200) && ($data->data->token)) {
   $this->settoken($data->data->token);
   $this->isloggedin = true;
   }
return $data;
}


public function search($q, $website,$ip = null,$referer = null) {
$res = $this->client->request('POST','search',['headers' => ['Authorization' => 'Bearer ' . $this->gettoken()],'form_params' => ['q' => $q, 'website' => $website,'ip' => $ip,'referer' => $referer]]);
return $this->handlereply($res);
}

public function languages() {
$res = $this->client->request('GET','languages',['headers' => ['Authorization' => 'Bearer ' . $this->gettoken()]]);
return $this->handlereply($res);
}

protected function handlereply($res) {

$codes = array('200','412','401','403');

if (in_array($res->getStatusCode(),$codes)) {
$body =  $res->getBody();
return $this->output($body->getContents());
} else {
return (object) array('code' => $res->getStatusCode(), 'message' => $res->getReasonPhrase());
    }
}

public function locations() {
$res = $this->client->request('GET','locations',['headers' => ['Authorization' => 'Bearer ' . $this->gettoken()]]);
$body =  $res->getBody();
return $this->output($body->getContents());
}

public function loggedin() {
   return $this->isloggedin;
   }

protected function gettoken() {
	return $this->token;
}
protected function settoken($token) {
	$this->token = $token;
}

protected function output($output) {
	return json_decode($output);
}

}
?>
