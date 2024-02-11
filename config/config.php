<?php

return [
   'settings' => [ 
                   'displayErrorDetails' => false,
                   'logErrorDetails' => true,
                   'debug' => true,
                   'version' => '2.2.3',
                   'logger' => [
                   'name' => 'cms.hostingbe',
                   'path' => __DIR__ . '/../logs/'.date('d-m-Y').'-app.log',
                   'level' => 'DEBUG',
                        ],
                   'db'  => [
     
     'host' => 'localhost',
     'username' => 'cms',
     'database' => 'cms',
     'password' => 'dUG4GPldpQjbkT6',

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
