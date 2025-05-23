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

use PDO;

class KeyWords {

protected $db;

public function __construct(PDO $db) {
	$this->db = $db;
	}


public function getKeyWords() {

$sql = $this->db->prepare("SELECT CONCAT('/blog-',id,'-',lower(replace(title,' ', '-')),'/') as link,tags FROM blog LIMIT 50");
$sql->execute();
$blogkeywords = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT CONCAT('/',name) as link,keywords as tags FROM pages LIMIT 50");
$sql->execute();
$pagekeywords = $sql->fetchALL(PDO::FETCH_OBJ);

$sql = $this->db->prepare("SELECT CONCAT('/support-',id,'-',lower(replace(titel,' ', '-')),'/') as link,tags FROM artikelen LIMIT 50");
$sql->execute();
$supportkeywords = $sql->fetchALL(PDO::FETCH_OBJ);

    return $this->merge((array) $blogkeywords,(array) $pagekeywords,(array) $supportkeywords);
    }

private function merge($blogkeywords,$pagekeywords,$supportkeywords) :array {
    $merged = array();
    foreach ($blogkeywords as $kwd) {
       $merged[] = $kwd;
       }
    foreach ($pagekeywords as $kwd) {
       $merged[] = $kwd;
       }
      foreach ($supportkeywords as $kwd) {
       $merged[] = $kwd;
       }     
    return (array) $merged;
    }
}

?>
