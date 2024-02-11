<?php

/**
 * @author Constan van Suchtelen van de Haere <constan@hostingbe.com>
 * @copyright 2023 HostingBE
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
