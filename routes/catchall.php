<?php

use App\Controllers\Page;


$app->get('/[{page:[a-zA-Z0-9\-]+}]', Page::class . ':show')->setName('page.show')->add('csrf');

?>