<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;


use App\Controllers\Account;
use App\Controllers\Advertenties;
use App\Controllers\Blog;
use App\Controllers\Category;
use App\Controllers\Contact;
use App\Controllers\Dashboard;
use App\Controllers\Email;
use App\Controllers\Forum;
use App\Controllers\Manager;
use App\Controllers\Media;
use App\Controllers\Page;
use App\Controllers\Partner;
use App\Controllers\Search;
use App\Controllers\Settings;
use App\Controllers\Support;
use App\Controllers\Templates;
use App\Controllers\Todo;
use App\Controllers\Login;
use App\Controllers\Logging;
use App\Controllers\Users;



$app->group('', function($csrfroute) {
$csrfroute->get('/status', Page::class . ':status')->setName('page.status');
$csrfroute->get('/logout', Login::class . ':logout')->setName('login.logout');
$csrfroute->get('/blog', Blog::class . ':overview')->setName('blog.overview');
$csrfroute->get('/login', Login::class . ':login')->setName('login.login');
$csrfroute->get('/contact-form', Contact::class . ':show')->setName('contact.show');
$csrfroute->get('/create-account', Account::class . ':create_account')->setName('account.create_account');
$csrfroute->get('/request-password', Account::class . ':request_password')->setName('account.request_password');
$csrfroute->get('/activate-user/{code}/{email}/', Account::class . ':activate')->setName('account.activate');
$csrfroute->get('/request-activation-code', Account::class . ':request_code')->setName('account.request_code');
$csrfroute->get('/bevestig-wachtwoord/{gebruiker}/{email}/{code}',Account::class . ':confirm_password')->setName('account.confirm_password');
$csrfroute->get('/delete-user/{code}/{email}/', Account::class . ':delete')->setName('account.delete');
$csrfroute->get('/blog-{id:[0-9]+}-{title:[^\/]+}/', Blog::class . ':view')->setName('blog.view');
$csrfroute->get('/seo-blog/{id:[0-9]+}-{category:[^\/]+}/', Blog::class . ':category')->setName('blog.category');
$csrfroute->get('/advertentie', Advertenties::class . ':advertentie')->setName('advertenties.advertentie');
$csrfroute->get('/outgoing-link/{id:[0-9]+}/{code:[a-zA-Z0-9]+}/', Advertenties::class . ':outgoing')->setName('advertenties.outgoing');

$csrfroute->get('/search/{q:[a-zA-Z0-9\-]+}/', Search::class . ':search')->setName('search.search');

$csrfroute->get('/seo-support', Support::class . ':overview')->setName('support.overview');
$csrfroute->get('/seo-support/{id:[0-9]+}/{name:[^\/]+}/', Support::class . ':view_category')->setName('support.view_category');
$csrfroute->get('/support/{id:[0-9]+}-{title:[^\/]+}/', Support::class . ':view')->setName('support.view');
$csrfroute->get('/view-email/{code:[^\/]{32}}/{hash:[a-z0-9]{64}}/',Email::class . ':view')->setName('email.view');

$csrfroute->get('/apisearch', Search::class . ':apisearch')->setName('search.apisearch');


$csrfroute->post('/support-like', Support::class . ':post_like');

$csrfroute->post('/ask-question', Forum::class . ':postask');
$csrfroute->post('/topic-reply', Forum::class . ':postreply');
$csrfroute->post('/topic-upload',Forum::class . ':topic_upload');
$csrfroute->post('/forum-like', Forum::class . ':post_like');

$csrfroute->post('/create-account', Account::class . ':post_create_account');
$csrfroute->post('/login', Login::class . ':post_login')->setName('login.post_login');
$csrfroute->post('/contact-form', Contact::class . ':postcontact');
$csrfroute->post('/post-comment/{id:[0-9]+}/', Blog::class . ':postcomment');
$csrfroute->post('/request-password', Account::class . ':post_request_password');
$csrfroute->post('/request-code', Account::class . ':post_request_code');
$csrfroute->post('/post-comment', Blog::class . ':post_comment');
$csrfroute->post('/blog-search', Blog::class . ':search')->setName('blog.search');

$csrfroute->post('/post-support-comment', Support::class . ':post_support_comment');
$csrfroute->post('/support-search', Support::class . ':search')->setName('support.search');


$csrfroute->post('/search', Search::class . ':search');

// FORUM overview
$csrfroute->get('/forum', Forum::class . ':overview')->setName('forum.overview');
$csrfroute->get('/forum/{name:[^\/]+}/{id:[0-9]+}/', Forum::class . ':overview_category')->setName('forum.overview_category');
$csrfroute->get('/ask-question', Forum::class . ':ask')->setName('forum.ask');
$csrfroute->get('/ask-question-files', Forum::class . ':get_files')->setName('forum.get_files');
$csrfroute->get('/{title:[^\/]+}/topic-{id:[0-9]+}/', Forum::class . ':view')->setName('forum.view');
$csrfroute->get('/topic-delete-all', Forum::class . ':delete_all');
$csrfroute->get('/topic-delete-file/{filename:[a-z0-9]+\.[a-z]{3,4}}', Forum::class . ':delete_file');
})->add('csrf');

?>
