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
namespace App\Controllers\Manager;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Gumlet\ImageResize;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use JasonGrimes\Paginator;

class Media {
	
protected $view;
protected $db;
protected $flash;
protected $mail;
protected $logger;
protected $settings;
protected $directory = __DIR__ . '/../../../public_html/uploads/';
protected $allowed = array('jpg','jpeg','png', 'gif');

  
public function __construct(Twig $view, $db, $flash, $mail, $logger, $settings) {
$this->view = $view;
$this->db = $db;
$this->flash = $flash;
$this->mail = $mail;
$this->logger = $logger;
$this->settings = $settings;

}


public function delete(Request $request,Response $response) {

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

public function post_name(Request $request,Response $response) {

 $data = $request->getParsedBody();

$v = new Validator($data); 
$v->rule('required','filename');
$v->rule('required','filenameold');
$v->rule('length','filename',3, 64);
$v->rule('length','filenameold',3, 64);

$sql = $this->db->prepare("SELECT id,naam,extentie FROM media WHERE naam=:naamoud LIMIT 1");
$sql->bindparam(":naamoud",$data['filenameold'],PDO::PARAM_STR);
$sql->execute();
$media = $sql->fetch(PDO::FETCH_OBJ);

if (file_exists($this->directory . $media->naam .".".$media->extentie)) {
   rename($this->directory . $media->naam.".".$media->extentie, $this->directory . $data['filename'].".".$media->extentie);
   }

foreach (array('800','400','200') as $w) {
if (file_exists($this->directory . $media->naam."-". $w .".".$media->extentie)) {
   rename($this->directory . $media->naam."-". $w .".".$media->extentie, $this->directory . $data['filename']."-". $w .".".$media->extentie);
   }   
if (file_exists($this->directory . $media->naam."-". $w .".webp")) {
   rename($this->directory . $media->naam."-". $w .".webp", $this->directory . $data['filename']."-". $w .".webp");
   }
}


$sql = $this->db->prepare("UPDATE media SET naam=:naam WHERE id=:id AND naam=:naamoud");
$sql->bindparam(":id",$media->id,PDO::PARAM_INT);
$sql->bindparam(":naamoud",$data['filenameold'],PDO::PARAM_STR);
$sql->bindparam(":naam",$data['filename'],PDO::PARAM_STR);
$sql->execute();

$this->logger->warning(get_class() . ": bestand " . $media->naam . ".".$media->extentie . " is renamed to file " . $data['filename']); 

$response->getBody()->write(json_encode(array('status' => 'success','message' => "filename succesfully changed to " . $filename . "!", 'filename' => $data['filename'])));
return $response;
}


public function post_alt(Request $request,Response $response) {

 $data = $request->getParsedBody();

$v = new Validator($data); 
$v->rule('required','id');
$v->rule('length','alt',3,32);

$sql = $this->db->prepare("UPDATE media SET alt=:alt WHERE id=:id");
$sql->bindparam(":id",$data['id'],PDO::PARAM_INT);
$sql->bindparam(":alt",substr($data['alt'],0,128),PDO::PARAM_STR);
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
          $fullfilename = (new \App\Helpers\Helpers)->moveUploadedFile($this->directory, $uploadedFile);
          $this->logger->warning("manager heeft een media bestand geupload " . $fullfilename);
          }
          
          $extension = pathinfo($fullfilename, PATHINFO_EXTENSION);
          $filename = pathinfo($fullfilename, PATHINFO_FILENAME);
          $doctype = $uploadedFiles['file']->getClientMediaType();


         /**
          * als het een image is dan verwerken
          */
         if (in_array($extension,$this->allowed)) {

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
         
         list($width, $height) = @getimagesize($this->directory."/".$fullfilename);
         } else {
         $width = $height = 0;  
         }
         
         $size = round(filesize($this->directory."/".$fullfilename),2);

         $sql = $this->db->prepare("INSERT INTO media (naam,extentie,size,width,height,datum) VALUES(:naam,:extentie,:size,:width,:height,now())");
         $sql->bindparam(":naam",$filename,PDO::PARAM_STR);
         $sql->bindparam(":extentie",$extension,PDO::PARAM_STR);        
         $sql->bindparam(":size",$size,PDO::PARAM_STR); 
         $sql->bindparam(":width",$width,PDO::PARAM_INT); 
         $sql->bindparam(":height",$height,PDO::PARAM_INT);          
         $sql->execute();

         $response->getBody()->write(json_encode(array('status' => 'success','message' => 'bestand foto/video succesvol ge-upload!','files' => $filename.".".$extention)));
  
          return $response;
	     }


public function overview(Request $request,Response $response) {
   $page  = 1;

if ($request->getMethod() == "GET") {
        if ($request->getQueryParams()) {
        $page = $request->getQueryParams()['page'];
        }
    }

   $sql = $this->db->prepare("SELECT count(id) AS aantal FROM media");
   $sql->execute();
   $aantal = $sql->fetch(PDO::FETCH_OBJ);

    if ($request->getMethod() == "GET") {
    $url = (string) parse_url($request->getUri())['path']  . "?page=(:num)"; 
    }

   // aantal pagina's bepalen
   $start = $page * $this->settings['records'] - $this->settings['records'];       

   $pagelinks = new Paginator($aantal->aantal, $this->settings['records'], $page ,  $url);
   $pagelinks->setMaxPagesToShow(5);
   $pagelinks->setPreviousText('previous');
   $pagelinks->setNextText('next');

	$sql = $this->db->prepare("SELECT id,naam,extentie,(size/1024) AS size,alt,datum FROM media ORDER BY id DESC LIMIT :start,:records");
	$sql->bindparam(":start",$start,PDO::PARAM_INT);
   $sql->bindparam(":records",$this->settings['records'],PDO::PARAM_INT);        
   $sql->execute();
	$media = $sql->fetchALL(PDO::FETCH_OBJ);
	      
   $meta['title'] = "overview of media uploaded to your website";      
   $meta['description'] = "Manage media which is uploaded to your website, you can upload multiple media in a single click"; 
   $meta['keywords'] = "upload media,multiple uploads,images,images website,pdf website"; 

	      return $this->view->render($response,'manager/manager-media-overview.twig',['meta' =>  $meta,'huidig' => 'media-overzicht','media' => $media,'paginator' => $pagelinks,'url' => $this->settings['url'],'start' => $start,'aantal' => $aantal->aantal ]);
      }
}

?>