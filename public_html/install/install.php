<?php


require __DIR__ . '/assets/vendor/autoload.php';
require __DIR__ . '/src/htmloutput.php';

use Install\HtmlOutput;


$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/assets/templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
    'cache' => __DIR__ . '/assets/templates_c',
]);

$html = new HtmlOutput();

if ($_SERVER['REQUEST_METHOD'] == "GET") {
    $method = "get";
    $page;
    if (isset($_GET['page'])) { 
        $page = $_GET['page'];
        } 
 }
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $method = "post";
    }

if ($page == "admin") {
    echo $twig->render('admin.twig', ['title' => $html->getTitle(),'menu' => $html->getMenu() ]);
    }

if ($page == "version") {
    echo $twig->render('version.twig', ['title' => $html->getTitle(),'menu' => $html->getMenu(),'php' => $html->getPHPversion(),'modules' => $html->getModules() ]);
    }

if ($page == "database") {
    echo $twig->render('database.twig', ['title' => $html->getTitle(),'menu' => $html->getMenu() ]);
    }
if ($page == "voorwaarden") {
    echo $twig->render('voorwaarden.twig', ['title' => $html->getTitle(),'menu' => $html->getMenu(),'name' => $html->getName() ]);
    }
if ($page == "permissions") {
    echo $twig->render('permissions.twig', ['title' => $html->getTitle(),'menu' => $html->getMenu(),'name' => $html->getName(),'directories' => $html->getDirectories()]);
    }

        
if (!$page) {
    echo $twig->render('install.twig', ['title' => $html->getTitle(),'menu' => $html->getMenu() ]);
    }
?>