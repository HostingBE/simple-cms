<?php

return [
   'settings' => [ 
                   'displayErrorDetails' => false,
                   'logErrorDetails' => true,
                   'debug' => true,
                   'version' => '2.2.3',
                   'logger' => [
                   'name' => '[logname]',
                   'path' => __DIR__ . '/../logs/'.date('d-m-Y').'-app.log',
                   'level' => 'DEBUG',
                        ],
                   'db'  => [
     
     'host' => 'localhost',
     'username' => '[username]',
     'database' => '[database]',
     'password' => '[password]',

     ], 
     'translations' => [
            'path' => __DIR__."/../lang",
            'fallback' => 'en',
            'languages' => array('nl','en','de'), 
            'enabled' => true,
     ],
        ],
             
]

?>
