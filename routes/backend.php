<?php

/**
* @author Constan van Suchtelen van de Haere <constan.vansuchtelenvandehaere@hostingbe.com>
* @copyright 2024 - 2025 HostingBE
*
* Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
* files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy,
* modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software
* is furnished to do so, subject to the following conditions:

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
* THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF
* OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*
*/

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