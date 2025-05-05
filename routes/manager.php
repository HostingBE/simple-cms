<?php


use App\Controllers\Account;
use App\Controllers\Advertenties;
use App\Controllers\Blog;
use App\Controllers\Manager\Category;
use App\Controllers\Chat;
use App\Controllers\Dashboard;
use App\Controllers\Forum;
use App\Controllers\Manager\Links;
use App\Controllers\Manager;
use App\Controllers\Manager\Media;
use App\Controllers\Page;
use App\Controllers\Settings;
use App\Controllers\Manager\Settings as ManagerSettings; 
use App\Controllers\Support;
use App\Controllers\Templates;
use App\Controllers\Manager\Todo;
use App\Controllers\Manager\Logging;
use App\Controllers\Manager\Upload;
use App\Controllers\Users;

$checkManager = new \App\Middleware\CheckManager();

$app->group('', function($route) {
$route->get('/manager/dashboard', Dashboard::class . ':manager_overview')->setName('dashboard.manager_overview');
$route->get('/manager/pagina-toevoegen', Manager::class . ':pagina_toevoegen')->setName('manager.pagina_toevoegen');
$route->get('/manager/bewerken-pagina/{pagina:[0-9]+}/', Manager::class . ':pagina_bewerken')->setName('manager.pagina_bewerken');
$route->get('/manager/pagina-overzicht', Manager::class . ':manager_overview')->setName('manager.manager_overview');
$route->get('/manager/verwijder-pagina/{pagina:[0-9]+}/', Manager::class . ':verwijder_pagina')->setName('manager.verwijder_pagina');
$route->get('/manager/blog-toevoegen', Blog::class . ':blog_toevoegen')->setName('blog.blog_toevoegen');
$route->get('/manager/blog-overzicht', Blog::class . ':manager_overview')->setName('blog.manager_overview');
$route->get('/manager/links-overview', Links::class . ':overview')->setName('links.overview');
$route->get('/manager/category-overview', Category::class . ':overview')->setName('category.overview');
$route->get('/manager/blog-bewerken/{id:[0-9]+}/', Blog::class . ':bewerken')->setName('blog.bewerken');
$route->get('/manager/verwijder-blog/{id:[0-9]+}/', Blog::class . ':verwijder')->setName('blog.verwijder');
$route->get('/manager/todo-overview', Todo::class . ':overview');
$route->get('/manager/templates-overzicht', Templates::class . ':overview');
$route->get('/manager/edit-template/{file:[^\/]+}/', Templates::class . ':edit');
$route->get('/manager/todo-edit/{id:[^\/]+}/', Todo::class . ':bewerken');
$route->get('/manager/delete-todo/{id:[0-9]+}/', Todo::class . ':verwijder');
$route->get('/manager/view-logging', Logging::class . ':view')->setName('logging.view');
$route->get('/manager/do-bekijk-logging/{file}/', Logging::class . ':post');
$route->get('/manager/media-overview', Media::class . ':overview')->setName('media.overview');
$route->get('/manager/delete-media/{id:[0-9]+}/', Media::class . ':delete')->setName('media.delete');
$route->get('/manager/support-toevoegen', Support::class . ':toevoegen')->setName('support.toevoegen');
$route->get('/manager/support-overzicht', Support::class . ':manager_overview')->setName('support.manager_overview');
$route->get('/manager/support-bewerken/{id:[0-9]+}/', Support::class . ':bewerken')->setName('support.bewerken');
$route->get('/manager/verwijder-support/{id:[0-9]+}/', Support::class . ':verwijder')->setName('support.verwijder');
$route->get('/manager/gebruikers-overzicht', Users::class . ':manager_overview')->setName('users.manager_overview');
$route->get('/manager/verwijder-gebruiker/{id:[0-9]+}/', Users::class . ':manager_verwijder');
$route->get('/manager/bekijk-gebruiker/{id:[0-9]+}/', Users::class . ':manager_view');
$route->get('/manager/delete-category/{id:[0-9]+}/', Category::class . ':delete');
$route->get('/manager/settings-overview', ManagerSettings::class . ':overview');
$route->get('/manager/advertenties-overview', Advertenties::class . ':overview');
$route->get('/manager/advertentie-toevoegen', Advertenties::class . ':toevoegen');
$route->get('/manager/advertentie-bewerken/{id:[0-9]+}/', Advertenties::class . ':bewerken');
$route->get('/manager/verwijder-advertentie/{id:[0-9]+}/', Advertenties::class . ':verwijder');
$route->get('/manager/chat-overview', Chat::class . ':overview');
$route->get('/manager/view-chat/{id:[0-9]+}/{session:[0-9a-zA-Z]+}/', Chat::class . ':manager_view');

$route->post('/manager/advertentie-toevoegen', Advertenties::class . ':post_toevoegen');
$route->post('/manager/advertentie-bewerken/{id:[0-9]+}/', Advertenties::class . ':post_bewerken');
$route->post('/manager/upload-image', Upload::class . ':tinymceImage');
$route->post('/manager/category-add', Category::class . ':post_add');
$route->post('/manager/support-toevoegen', Support::class . ':post_toevoegen');
$route->post('/manager/support-bewerken/{id:[0-9]+}/', Support::class . ':post_bewerken');
$route->post('/manager/pagina-toevoegen', Manager::class . ':post_pagina_toevoegen');
$route->post('/manager/pagina-bewerken/{pagina:[0-9]+}/', Manager::class . ':post_pagina_bewerken');
$route->post('/manager/blog-toevoegen', Blog::class . ':post_toevoegen');
$route->post('/manager/blog-bewerken/{id:[0-9]+}/', Blog::class . ':post_bewerken');
$route->post('/manage/toevoegen-todo', Todo::class . ':post_toevoegen');
$route->post('/manager/todo-bewerken/{id:[0-9]+}/', Todo::class . ':post_bewerken');
$route->post('/manager/upload-media', Media::class . ':post_upload');
$route->post('/manager/alt-media', Media::class . ':post_alt');
$route->post('/manager/add-link', Links::class . ':post_add');
$route->post('/manager/delete-links', Links::class . ':delete');
$route->post('/manager/edit-template/{file:[^\/]+}/', Templates::class . ':post_edit');
$route->post('/manager/settings', ManagerSettings::class . ':save');
})->add($checkManager)->add('csrf');


?>