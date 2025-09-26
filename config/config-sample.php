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

     'host' => getenv('db'),
     'username' => getenv('username'),
     'database' => getenv('database'),
     'password' => getenv('password'),

     ],
     'translations' => [
            'path' => __DIR__."/../lang",
            'fallback' => 'en',
            'languages' => array(
                           array('url' => 'simple-cms-nl.hostingbe.lan', 'language' => 'nl', 'spoken' => 'nederlands'),
                           array('url' => 'simple-cms.hostingbe.lan', 'language' => 'en', 'spoken' => 'english'),
                           array('url' => 'simple-cms-de.hostingbe.lan', 'language' => 'de','spoken' => 'deutsch')),
            'enabled' => true,
     ],
        ],

]

?>
