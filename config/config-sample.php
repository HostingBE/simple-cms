<?php

return [
   'settings' => [
                   'displayErrorDetails' => true,
                   'logErrorDetails' => true,
                   'debug' => true,
                   'version' => getenv('version'),
                   'logger' => [
                   'name' => '[logname]',
                   'path' => __DIR__ . '/../logs/'.date('d-m-Y').'-app.log',
                   'level' => 'DEBUG',
                        ],
                   'db'  => [

     'host' => getenv('host'),
     'username' => getenv('username'),
     'database' => getenv('database'),
     'password' => getenv('password'),

     ],
     'translations' => [
            'path' => __DIR__."/../lang",
            'fallback' => 'en',
            'languages' => array(
                           array('url' => 'nl-'.$_SERVER['HTTP_HOST'], 'language' => 'nl', 'spoken' => 'nederlands'),
                           array('url' => $_SERVER['HTTP_HOST'], 'language' => 'en', 'spoken' => 'english'),
                           array('url' => 'de-'.$_SERVER['HTTP_HOST'], 'language' => 'de','spoken' => 'deutsch')),
            'enabled' => true,
     ],
        ],

]

?>
