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


namespace App\Content;


class ResizeImage {

protected $width;
protected $height;
protected $new;

public function __construct(int $width, int $height, $new) {
    $this->width = $width;
    $this->height = $height;
    $this->new = $new;
}

public function crop() {

if ($this->orientation() == 'l') {
    $onepct = $this->width / 100;
    $ratio = $this->new / $onepct;
    return $this->new .":". round($this->height * "0.$ratio", 0, PHP_ROUND_HALF_UP);   
}

if ($this->orientation() == 'p') {
    $onepct = $this->height / 100;
    $ratio = $this->new / $onepct;
    return round($this->height * "0.$ratio", 0, PHP_ROUND_HALF_UP)  .":". $this->height;
    }

}

private function orientation() {

    if ($this->width > $this->height) {
        return 'l';
        }
    if ($this->width < $this->height) {
        return 'p';
        }    
    if ($this->width == $this->height) {
        return 's';
        } 
    }

}



?>