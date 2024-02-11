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
use Valitron\Validator;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Gumlet\ImageResize;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class Media {
	
protected $view;
protected $db;
protected $flash;
protected $mail;
protected $logger;
protected $settings;
protected $directory = __DIR__ . '/../../public_html/uploads/';
  
public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings) {
$this->view = $view;
$this->db = $db;
$this->flash = $flash;
$this->mail = $mail;
$this->logger = $logger;
$this->settings = $settings;

}


public function verwijder(Request $request,Response $response) {

$id  = $request->getattribute('id');

$sql = $this->db->prepare("SELECT id,naam,extentie FROM media WHERE id=:id");
$sql->bindparam(":id",$id,PDO::PARAM_INT);
$sql->execute();
$media = $sql->fetch(PDO::FETCH_OBJ);


$user = Sentinel::getUser();

if (file_exists($this->directory . $media->naam .".".$media->extentie)) {
   unlink($this->directory . $media->naam.".".$media->extentie);
   }
if (file_exists($this->directory . "support/".$user->id."/".$media->naam.".".$media->extentie)) {
   unlink($this->directory . "support/".$user->id."/".$media->naam.".".$media->extentie);
   }

foreach (array('800','400','200') as $w) {
if (file_exists($this->directory . $media->naam."-". $w .".".$media->extentie)) {
   unlink($this->directory . $media->naam."-". $w .".".$media->extentie);
   }   
if (file_exists($this->directory . $media->naam."-". $w .".webp")) {
   unlink($this->directory . $media->naam."-". $w .".webp");
   }
}

$sql = $this->db->prepare("DELETE FROM media WHERE id=:id");
$sql->bindparam(":id",$media->id,PDO::PARAM_INT);
$sql->execute();

$response->getBody()->write("media item succesvol verwijderd!");
return $response;    
}

public function post_alt(Request $request,Response $response) {

 $data = $request->getParsedBody();

$v = new Validator($data); 
$v->rule('required','id');
$v->rule('length','alt',3,32);

$sql = $this->db->prepare("UPDATE media SET alt=:alt WHERE id=:id");
$sql->bindparam(":id",$data['id'],PDO::PARAM_INT);
$sql->bindparam(":alt",$data['alt'],PDO::PARAM_STR);
$sql->execute();


$response->getBody()->write(json_encode(array('status' => 'success','message' => "alt text of image succesfully changed!")));
return $response;
}


public function post_upload(Request $request,Response $response) {
  
         
         $uploadedFiles = (array) ($request->getUploadedFiles() ?? []);

         $max_upload = (int)(ini_get('upload_max_filesize') * 1024 * 1024);
         $max_post = (int)(ini_get('post_max_size') * 1024 * 1024);
         $memory_limit = (int)(ini_get('memory_limit') * 1024 * 1024);
         $upload_mb = min($max_upload, $max_post, $memory_limit);     




    	   if (!$uploadedFiles['file']->getSize()) {
         $response->getBody()->write(json_encode(array('status' => 'error','message' => "er is geen bestand geselecteerd om te verzenden!")));
	       return $response;
	       }

    	   if ($uploadedFiles['file']->getSize() > $upload_mb) {
         $response->getBody()->write(json_encode(array('status' => 'error','message' => "Bestand met grootte " . $uploadedFiles['file']->getSize() . " met limiet " . $upload_mb . " te groot om te verzenden!")));
	       return $response;
	       }
	       
	       if (!is_dir($this->directory)) {
   	     mkdir($this->directory);
   	     }
   	     

          // handle single input with single file upload
          $uploadedFile = $uploadedFiles['file'];
          if ($uploadedFile->getError() === \UPLOAD_ERR_OK) {
          $fullfilename = moveUploadedFile($this->directory, $uploadedFile);
          $this->logger->warning("manager heeft een media bestand geupload " . $fullfilename);
          }
          
          $extension = pathinfo($fullfilename, PATHINFO_EXTENSION);
          $filename = pathinfo($fullfilename, PATHINFO_FILENAME);
          $doctype = $uploadedFiles['file']->getClientMediaType();


         foreach (array('800','400','200') as $w) {
         $image = new ImageResize($this->directory."/".$fullfilename);
         $image->resizeToWidth($w, $allow_enlarge = True); 
         $image->save($this->directory."/".$filename."-". $w.".".$extension);

         $optimizerChain = OptimizerChainFactory::create();
         $optimizerChain->optimize($this->directory."/".$filename."-". $w.".".$extension);
         
         switch ($doctype) {
               case 'image/jpeg':
               $img  = @imagecreatefromjpeg($this->directory."/".$filename."-". $w.".".$extension);
               break;
              case 'image/gif':
               $img  = @imagecreatefromgif($this->directory."/".$filename."-". $w.".".$extension);
               break;
                 case 'image/png':
               $img  = @imagecreatefrompng($this->directory."/".$filename."-". $w.".".$extension);
               break;
               }
         $webp = imagewebp($img, $this->directory."/".$filename."-". $w.".webp"); 
         
         imagedestroy($img);
         }

         $size = round(filesize($this->directory."/".$fullfilename),2);
         list($width, $height) = @getimagesize($this->directory."/".$fullfilename);
         
         $sql = $this->db->prepare("INSERT INTO media (naam,extentie,size,width,height,datum) VALUES(:naam,:extentie,:size,:width,:height,now())");
         $sql->bindparam(":naam",$filename,PDO::PARAM_STR);
         $sql->bindparam(":extentie",$extension,PDO::PARAM_STR);        
         $sql->bindparam(":size",$size,PDO::PARAM_STR); 
         $sql->bindparam(":width",$width,PDO::PARAM_INT); 
         $sql->bindparam(":height",$height,PDO::PARAM_INT);          
         $sql->execute();

         $response->getBody()->write(json_encode(array('status' => 'success','message' => 'bestand foto/video succesvol ge-upload!')));
  
          return $response;
	     }


public function overview(Request $request,Response $response) {
	       
	      $sql = $this->db->prepare("SELECT id,naam,extentie,(size/1024) AS size,alt,datum FROM media ORDER BY id DESC");
	      $sql->execute();
	      $media = $sql->fetchALL(PDO::FETCH_OBJ);
	      

	      return $this->view->render($response,'manager/manager-media-overview.twig',['meta' =>  $meta,'huidig' => 'media-overzicht','media' => $media]);
      }
}

?>