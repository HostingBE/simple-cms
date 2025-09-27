<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;


use App\Controllers\Account;
use App\Controllers\Dashboard;
use App\Controllers\Google2FA;
use App\Controllers\Partner;
use App\Controllers\Settings;

$checkUser = new \App\Middleware\CheckUser();

$app->group('', function($route) {
$route->get('/settings',Settings::class . ':overview');
$route->get('/api-settings',Settings::class . ':api');
$route->get('/my-account',Account::class . ':info');
$route->get('/change-password', Account::class . ':change_password')->setName('account.change_password');
$route->get('/backend/dashboard', Dashboard::class . ':overview')->setName('dashboard.overview');
$route->get('/affiliate-overview',Partner::class . ':overzicht')->setName('partner.overzicht');
$route->get('/delete-account', Account::class . ':delete_account')->setName('account.delete_account');

$route->post('/verify', Google2FA::class . ':verify');
$route->post('/upload-icon',Account::class . ':upload_icon');
$route->post('/my-account', Account::class . ':post_account_info');
$route->post('/change-password', Account::class . ':post_change_password');
$route->post('/settings',Settings::class . ':save');
$route->post('/api-settings',Settings::class . ':post_api');
})->add($checkUser)->add('csrf');



?>