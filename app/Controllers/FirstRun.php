<?php

/**
 * @author Constan van Suchtelen van de Haere <constan@hostingbe.com>
 * @copyright 2024 HostingBE
 */

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;

class FirstRun {

    protected $view;
    protected $db;
    protected $logger;


public function __construct(Twig $view, $db, $logger) {
    $this->view = $view;
    $this->db = $db;
    $this->logger = $logger;
}

public function install() {
    $this->setSettings();
    $this->createPage();
    $this->removeInstallFile();
    $this->attachRoles();
    }

private function removeInstallFile() {
    if (file_exists(__DIR__. '/../../.new_install')) {
          unlink(__DIR__. '/../../.new_install');
          }
    
    }
private function attachRoles() {
$roles = array('administrator' => array('name' => 'administrator','created_at' => date('Y-m-d'),'updated_at' => date('Y-m-d')),
               'customer' => array('name' => 'customer','created_at' => date('Y-m-d'),'updated_at' => date('Y-m-d')),
               'visitor' => array('name' => 'visitor','created_at' => date('Y-m-d'),'updated_at' => date('Y-m-d')),
                );


    $sql = $this->db->prepare("INSERT INTO roles(slug,name,created_at,updated_at) VALUES(:slug,:name,:created_at,:updated_at)");

    foreach ($roles as $key => $val) {
        $sql->bindparam(":slug",$key,PDO::PARAM_STR);
        $sql->bindparam(":name",$val['name'],PDO::PARAM_STR);
        $sql->bindparam(":created_at",$val['created_at'],PDO::PARAM_STR);
        $sql->bindparam(":updated_at",$val['updated_at'],PDO::PARAM_STR);
        $sql->execute();
        }
}


private function setSettings() {
$settings = array('sitename' => 'CMS HostingBE',
                'bgcolor' => 'blue',
                'color' => 'white',
                'records' => '10',
                'html_email' => 'on',
                'cache' => 'on',
                'footer' => '',
                'management_ip' => $_SERVER['REMOTE_ADDR'],
                'email' => '',
                'email_name' => '',
                'multilanguage' => 'off',
                'htmleditor' => 'on',
                'disablesupport' => 'off',
                'disableforum' => 'off',
                'markdown' => 'off',  
                );

$sql = $this->db->prepare("INSERT INTO website_settings(setting,value) VALUES(:setting,:value)");

foreach ($settings as $key => $val) {
    $sql->bindparam(":setting",$key,PDO::PARAM_STR);
    $sql->bindparam(":value",$val,PDO::PARAM_STR);
    $sql->execute();
    }
$this->logger->warning(__CLASS__ . ": new website settings imported in the database!");
}

private function createPage() {
    
$content = $this->view->fetch('frontend/main.twig');

$page = (object) array(
     'name' => 'index',
     'titel' => 'First page',
     'description' => 'First page for CMS simple',
     'keywords' => 'CMS, php,slim,hostingbe',
     'template' => 'template.twig',
     'content' => $content,
     'publish'=> 'y',
     'language' => 'en',
     'datum' => date('Y-m-d'),
     );
    

$sql = $this->db->prepare("INSERT INTO pages (name,titel,description,keywords,template,content,publish,publish_date,language,datum) VALUES(:name,:titel,:description,:keywords,:template,:content,:publish,:publish_date,:language,now())");
$sql->bindparam(":name",$page->name,PDO::PARAM_STR);
$sql->bindparam(":titel",$page->titel,PDO::PARAM_STR);
$sql->bindparam(":description",$page->description,PDO::PARAM_STR);
$sql->bindparam(":keywords",$page->keywords,PDO::PARAM_STR);
$sql->bindparam(":template",$page->template,PDO::PARAM_STR);
$sql->bindparam(":content",$page->content,PDO::PARAM_STR);
$sql->bindparam(":publish",$page->publish,PDO::PARAM_STR,1);
$sql->bindparam(":publish_date",$page->datum,PDO::PARAM_STR);
$sql->bindparam(":language",$page->language,PDO::PARAM_STR);
$sql->execute();

$this->logger->warning(__CLASS__ . ": new page created no index found!");

}

}
?>
