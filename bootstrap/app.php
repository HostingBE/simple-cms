<?php

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Cartalyst\Sentinel\Native\SentinelBootstrapper;

session_start();

require __DIR__ . '/../vendor/autoload.php';

Sentinel::instance(

     new SentinelBootstrapper(
     require(__DIR__ . '/../config/auth.php')
     )
);

require __DIR__ . '/container.php';
require __DIR__ . '/../app/Custom/bootstrap/container.php';
require __DIR__ . '/middleware.php';
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../app/Custom/routes/routes.php';
require __DIR__ . '/../routes/backend.php';
require __DIR__ . '/../routes/manager.php';
require __DIR__ . '/../routes/catchall.php';

$app->run();

?>