<?php

return [
   'settings' => [ 
                   'displayErrorDetails' => true,
                   'logErrorDetails' => true,
                   'debug' => true,
                   'version' => '2.2.4',
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
