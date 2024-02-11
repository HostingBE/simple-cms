<?php

namespace App\Controllers\Manager;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Gumlet\ImageResize;
use Cartalyst\Sentinel\Native\Facades\Sentinel; 
use Valitron\Validator;
use Slim\Exception\HttpNotFoundException;
use Spatie\ImageOptimizer\OptimizerChainFactory;


class Upload {
protected $view;
protected $db;
protected $directory = __DIR__ . '/../../../public_html/uploads/';
protected $flash;
protected $logger;
protected $mail;
protected $settings;

public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings, $locale) {
$this->view = $view;
$this->db = $db; 
$this->flash = $flash;
$this->mail = $mail;       
$this->logger = $logger;
$this->settings = $settings;
$this->locale = $locale;
      }


public function tinymceImage(Request $request,Response $response) {


$accepted_origins = array($this->settings['url']);

if (isset($_SERVER['HTTP_ORIGIN'])) {
    // same-origin requests won't set an origin. If the origin is set, it must be valid.
    if (in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
        $response = $response->withAddedHeader('Access-Control-Allow-Origin', $_SERVER['HTTP_ORIGIN']);
        } else {
        $response = $response->withStatus(403);
        return $response;
        }
}


$uploadedFiles = (array) ($request->getUploadedFiles() ?? []);


$max_upload = (int)(ini_get('upload_max_filesize') * 1024 * 1024);
$max_post = (int)(ini_get('post_max_size') * 1024 * 1024);
$memory_limit = (int)(ini_get('memory_limit') * 1024 * 1024);
$upload_mb = min($max_upload, $max_post, $memory_limit);   

if ($uploadedFiles['file']->getSize() > $upload_mb) {
$response->getBody()->write("File with size " . $uploadedFiles['file']->getSize() . " limit " . $upload_mb . " to large to sent!");
return $response->withStatus(403);
}

if (!$uploadedFiles['file']->getSize()) {
$response->getBody()->write("No input file selected!");
return $response->withStatus(403);
}

             
if (!is_dir($this->directory)) {
mkdir($this->directory);
}

$uploadedFile = $uploadedFiles['file'];
if ($uploadedFile->getError() === \UPLOAD_ERR_OK) {

$filename = moveUploadedFile($this->directory, $uploadedFile);

$size = round(filesize($this->directory."/".$filename),2);
$this->logger->info(get_Class() . " File uploaded with tinymce editor " . $filename . " original size " . $size);

$image = new ImageResize($this->directory."/".$filename);
$image->resizeToWidth($this->settings['image_size'], $allow_enlarge = True);
$image->save($this->directory."/".$filename);

$optimizerChain = OptimizerChainFactory::create();
$optimizerChain->optimize($this->directory."/".$filename);

$sizenew = round(filesize($this->directory."/".$filename),2);
$this->logger->info(get_Class() . " Optimized file uploaded with tinymce editor " . $filename . " new size " . $sizenew);
}

$response->getBody()->write(json_encode(array('location' => $this->settings['url'].'/uploads/'.$filename)));
return $response;

      }
}



?>
