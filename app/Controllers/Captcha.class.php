<?php

namespace App\Controllers;

class Captcha {

protected $image;
protected $color;
protected $code;
protected $colors;

public function __construct() {
     $this->colors = $this->color_array();
     }


private function color_array() {
     $colors = array('blue' => array('r' => 23,'g' => 101, 'b' => 227),
                     'orange' => array('r' => 227,'g' => 125, 'b' => 23),
                     'yellow' => array('r' => 247,'g' => 174, 'b' => 71),
                     'red' => array('r' => 227,'g' => 23, 'b' => 23),
                     'white' => array('r' => 255,'g' => 255, 'b' => 255),
                     'black' => array('r' => 0,'g' => 0, 'b' => 0),
                     'green' => array('r' => 36,'g' => 227, 'b' => 23),
                     'purple' => array('r' => 132,'g' => 23, 'b' => 227), 
                     );
     return $colors;                  
     }

public function base_encode() {
     $base64 = 'data:image/' . $this->type . ';base64, ' . base64_encode($this->build());
     return $base64;
     } 

private function createpng() {
  return imagefrompng($this->build()); 
  } 

private function createjpg() {
  return imagefromjpeg($this->build()); 
  } 

private function createwebp() {
  return imagefromwebp($this->build()); 
  } 

public function settype($type) {
  $this->type = $type ?: 'jpeg';
  }

public function gettype() {
  return $this->type;
  }
public function getcode() {
  return $this->code;
  }

public function setbgcolor($color) {
    
  $this->r = $this->colors[$color]['r']; 
  $this->g = $this->colors[$color]['g'];
  $this->b = $this->colors[$color]['b'];
  }

public function setcolor($color) {
  $this->tr = $this->colors[$color]['r']; 
  $this->tg = $this->colors[$color]['g'];
  $this->tb = $this->colors[$color]['b'];
  }  

public function setcode($code) {
    $this->code = $code;
    }

public function create_som() {
  $een = rand(1,10);
  $twee = rand(1,10);
  $methods = array('+','-');
  
  if ($een >= $twee) {
    return $een . " " . $methods[array_rand($methods)] . " " .  $twee;
    }

  if ($twee >= $een) {
    return $twee . " " .  $methods[array_rand($methods)] . " " .  $een;
    }
  }  

public function create_string() {
  $string = random(6);
  return $string;
}

private function build() {
  

  $layer = imagecreatetruecolor(168, 37);
  $captcha_bg = imagecolorallocate($layer, $this->r, $this->g, $this->b);
  $captcha_text_color = imagecolorallocate($layer, $this->tr, $this->tg, $this->tb);
  imagefill($layer, 0, 0, $captcha_bg) or die;
  imagesetthickness($layer,2);
  imageline($layer, 0, 0, 89, 37, $captcha_text_color); 
  imageline($layer, 89, 0, 168, 37, $captcha_text_color);   
  imagestring($layer, 20, 55, 10, $this->getcode(), $captcha_text_color);

  ob_start();
  switch($this->gettype()) {
      case 'png' : $image = imagepng($layer); break;
      case 'jpeg': $image = imagejpeg($layer); break;
      case 'webp': $image = imagewebp($layer); break;
      case 'gif' : 
        $old_id = imagecreatefromgif($layer); 
        $image  = imagecreatetruecolor($img[0],$img[1]); 
        imagecopy($image,$old_id,0,0,0,0,$img[0],$img[1]); 
        break;
        default: break;
        }

      $image_data = ob_get_contents(); 
      ob_end_clean(); 
      return $image_data;
      }
}
