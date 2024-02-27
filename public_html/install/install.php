<?php

require 'src/htmloutput.php';


// configuration settings
$downloadurl = 'https://simple-cms.github.com';

$url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

$path = $_SERVER['SCRIPT_FILENAME'];

$phpversion = '8.1';

$directories = array(   'tmp' => array('name' => 'tmp','chmod' => '755'),
                        'data' => array('name' => 'data','chmod' => '755'),
                        'config' => array('name' => 'config','chmod' => '644'),
);




$html = new Install\htmloutput();


$html->header();

$html->getdatabase();

$html->footer();
?>