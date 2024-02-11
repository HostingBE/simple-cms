<?php

use Illuminate\Database\Capsule\Manager as Capsule;


$capsule = new Capsule();

$capsule->addConnection([
   'driver' => 'mysql',
   'host' => 'localhost',
   'database' => 'cms',
   'username' => 'cms',
   'password' => 'dUG4GPldpQjbkT6',
   'charset' => 'utf8',
   'collation' => 'utf8_unicode_ci'

]);

$capsule->bootEloquent();


?>
