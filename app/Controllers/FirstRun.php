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
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;
use Valitron\Validator;
use Cartalyst\Sentinel\Native\Facades\Sentinel as Sentinel;


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
    $this->createAdmin();
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

private function createAdmin() {

$passwd = (new \App\Helpers\Helpers)->RandomString(32);

$user = Sentinel::registerAndActivate([
'email' => 'admin@'.$_SERVER['HTTP_HOST'],
'password'=> $passwd,
'first_name'=> 'automatic',
'icon' => '',
'last_name' => 'created admin user',
'twofactor' => 'n']);

$Activation = Sentinel::getActivationRepository();
$activation = $Activation->create($user);

$role = Sentinel::findRoleByName('administrator');    
$role->users()->attach($user); 

$this->logger->info(__CLASS__.": New user administrator created with username admin@".$_SERVER['HTTP_HOST']." and password " . $passwd);
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
                'disablechat' => 'off',
                'url' => 'https://'.$_SERVER['HTTP_HOST'], 
                );

$sql = $this->db->prepare("INSERT INTO website_settings(setting, value) VALUES(:setting, :value)");

foreach ($settings as $key => $val) {
    $sql->bindparam(":setting",$key,PDO::PARAM_STR);
    $sql->bindparam(":value",$val,PDO::PARAM_STR);
    $sql->execute();
    }
$this->logger->warning(__CLASS__ . ": New website settings imported in the database!");
}

private function createPage() {
    
$content = $this->view->fetch('frontend/main.twig');
$contentnl = $this->view->fetch('frontend/main-nl.twig');
$contentde = $this->view->fetch('frontend/main-de.twig');

$pages[0] = (object) array(
     'name' => 'index',
     'titel' => 'Simple CMS the content management system for a fast SEO friendly website',
     'description' => 'With HostingBE\'s simple CMS, you can easily and quickly set up a website in multiple languages on multiple domains. The CMS is open source, so available for free, and is versatile and developed with SEO and speed as important basic features',
     'keywords' => 'simple cms, content management system, free php cms, security in mind',
     'template' => 'page-template.twig',
     'content' => $content,
     'publish'=> 'y',
     'language' => 'en',
     'datum' => date('Y-m-d'),
     );

$pages[1] = (object) array(
     'name' => 'index',
     'titel' => 'Simple CMS het content management systeem voor een snelle SEO-vriendelijke website',
     'description' => 'Met het eenvoudige CMS van HostingBE zet je eenvoudig en snel een website op in meerdere talen op meerdere domeinen. Het CMS is open source, dus gratis beschikbaar, veelzijdig en ontwikkeld met SEO en snelheid als belangrijke basisfuncties',
     'keywords' => 'simple cms, content management system, gratis php cms, veiligheid in gedachte',
     'template' => 'page-template.twig',
     'content' => $contentnl,
     'publish'=> 'y',
     'language' => 'nl',
     'datum' => date('Y-m-d'),
     );

$pages[2] = (object) array(
     'name' => 'index',
     'titel' => 'Einfaches CMS, das Content Management System für eine schnelle SEO-freundliche Website',
     'description' => 'Mit dem einfachen CMS von HostingBE können Sie einfach und schnell eine Website in mehreren Sprachen auf mehreren Domains einrichten. Das CMS ist Open Source, also kostenlos verfügbar.',
     'keywords' => 'einfaches CMS,SEO-freundliche,php CMS opensource',
     'template' => 'page-template.twig',
     'content' => $contentde,
     'publish'=> 'y',
     'language' => 'de',
     'datum' => date('Y-m-d'),
     );

$sql = $this->db->prepare("INSERT INTO pages (name,titel,description,keywords,template,content,publish,publish_date,language, datum) VALUES(:name,:titel,:description,:keywords,:template,:content,:publish,:publish_date,:language,now())");


foreach ($pages as $page) {
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
}

$this->logger->warning(__CLASS__ . ": new page created no index found!");

    }

}
?>
