<?php
use App\Console\Commands\ReminderEmail;
use App\Console\Commands\SearchEngine;

$app->add(new SearchEngine(
    $container->get('db'),
    $container->get('sitesettings'), 
    $container->get('logger'),
    $container->get('mail'),
    $container->get('view'))
    );

$app->add(new ReminderEmail(
    $container->get('db'),
    $container->get('sitesettings'), 
    $container->get('logger'),
    $container->get('mail'),
    $container->get('view'))
    );
?>
