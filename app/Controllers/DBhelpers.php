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