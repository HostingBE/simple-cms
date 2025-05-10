<?php

/**
 * @author Constan van Suchtelen van de Haere <constan@hostingbe.com>
 * @copyright 2023 HostingBE
 */

namespace App\Controllers;

use PDO;

class DBhelpers {

protected $db;
protected $locale;


public function __construct($db, $locale) {
    $this->db = $db;
    $this->locale = $locale;
    }

public function insert_log($user, $website, $prio = "info", $log = "no log entry") {
$sql = $this->db->prepare("INSERT INTO logging (user,website,prio,log,date) VALUES(:user,:website,:prio,:log,now())");
$sql->bindparam(":user",$user, PDO::PARAM_INT);
$sql->bindparam(":website",$website, PDO::PARAM_INT);
$sql->bindparam(":prio",$prio, PDO::PARAM_STR);
$sql->bindparam(":log",$log, PDO::PARAM_STR);
$sql->execute();
return $sql->errorInfo()['code'];
}    

public function random_toolsets() {

$sql = $this->db->prepare("SELECT id,name,url FROM toolsets ORDER BY rand() LIMIT 30");
$sql->execute();
$toolsets = $sql->fetchALL(PDO::FETCH_OBJ);
return $toolsets;
}    

public function get_support_categories() {
$soort = 'h'; 
$sql = $this->db->prepare("SELECT id,naam FROM categorie WHERE soort=:soort AND language=:locale ORDER BY naam ASC");
$sql->bindparam(":soort",$soort,PDO::PARAM_STR);
$sql->bindparam(":locale",$this->locale,PDO::PARAM_STR,2);
$sql->execute();
$categories = $sql->fetchALL(PDO::FETCH_OBJ);
return $categories;
}

public function get_visitors($command) {
       
   $sql = $this->db->prepare("SELECT count(id) AS aantal FROM commands WHERE ip=:ip AND command=:command");
   $sql->bindparam(":ip",get_client_ip(), PDO::PARAM_STR);
   $sql->bindparam(":command",$command, PDO::PARAM_STR);
   $sql->execute();
   $aantal = $sql->fetch(PDO::FETCH_OBJ);
   return $aantal->aantal;

      } 

}


?>