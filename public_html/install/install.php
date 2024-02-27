<?php

// configuration settings
$url = 'https://simple-cms.github.com';

$phpversion = '8.1';

$directories = array(   'tmp' => array('name' => 'tmp','chmod' => '755'),
                        'data' => array('name' => 'data','chmod' => '755'),
                        'config' => array('name' => 'config','chmod' => '644'),
);


echo "We gaan de install beginnen " . $phpversion;

?>