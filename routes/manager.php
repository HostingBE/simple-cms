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

use App\Controllers\Account;
use App\Controllers\Manager\Advertisements;
use App\Controllers\Blog;
use App\Controllers\Manager\Category;
use App\Controllers\Chat;
use App\Controllers\Dashboard;
use App\Controllers\Forum;
use App\Controllers\Manager\Links;
use App\Controllers\Manager\Manager;
use App\Controllers\Manager\Media;
use App\Controllers\Page;
use App\Controllers\Settings;
use App\Controllers\Manager\Settings as ManagerSettings;
use App\Controllers\Support;
use App\Controllers\Templates;
use App\Controllers\Manager\Todo;
use App\Controllers\Manager\Logging;
use App\Controllers\Manager\Upload;
use App\Controllers\Manager\Users;

$checkManager = new \App\Middleware\CheckManager();

$app->group('', function($route) {
$route->get('/manager/dashboard', Dashboard::class . ':manager_overview')->setName('dashboard.manager_overview');
$route->get('/manager/page-add', Manager::class . ':add')->setName('manager.add');
$route->get('/manager/edit-page/{pagina:[0-9]+}/', Manager::class . ':edit')->setName('manager.edit');
$route->get('/manager/add-setting', ManagerSettings::class . ':add')->setName('managersettings.add');
$route->get('/manager/pages-overview', Manager::class . ':overview')->setName('manager.overview');
$route->get('/manager/delete-page/{pagina:[0-9]+}/', Manager::class . ':delete')->setName('manager.delete');
$route->get('/manager/blog-add', Blog::class . ':add')->setName('blog.add');
$route->get('/manager/blogs-overview', Blog::class . ':manager_overview')->setName('blog.manager_overview');
$route->get('/manager/links-overview', Links::class . ':overview')->setName('links.overview');
$route->get('/manager/category-overview', Category::class . ':overview')->setName('category.overview');
$route->get('/manager/blog-edit/{id:[0-9]+}/', Blog::class . ':edit')->setName('blog.edit');
$route->get('/manager/delete-blog/{id:[0-9]+}/', Blog::class . ':delete')->setName('blog.delete');
$route->get('/manager/todo-overview', Todo::class . ':overview');
$route->get('/manager/templates-overview', Templates::class . ':overview');
$route->get('/manager/edit-template/{file:[^\/]+}/', Templates::class . ':edit');
$route->get('/manager/todo-edit/{id:[^\/]+}/', Todo::class . ':edit');
$route->get('/manager/delete-todo/{id:[0-9]+}/', Todo::class . ':delete');
$route->get('/manager/view-logging', Logging::class . ':view')->setName('logging.view');
$route->get('/manager/do-bekijk-logging/{file}/', Logging::class . ':post');
$route->get('/manager/media-overview', Media::class . ':overview')->setName('media.overview');
$route->get('/manager/delete-media/{id:[0-9]+}/', Media::class . ':delete')->setName('media.delete');
$route->get('/manager/add-support', Support::class . ':add')->setName('support.add');
$route->get('/manager/support-overview', Support::class . ':manager_overview')->setName('support.manager_overview');
$route->get('/manager/support-edit/{id:[0-9]+}/', Support::class . ':edit')->setName('support.edit');
$route->get('/manager/delete-support/{id:[0-9]+}/', Support::class . ':delete')->setName('support.delete');
$route->get('/manager/users-overview', Users::class . ':overview')->setName('users.overview');
$route->get('/manager/delete-user/{id:[0-9]+}/', Users::class . ':delete');
$route->get('/manager/view-user/{id:[0-9]+}/', Users::class . ':view');
$route->get('/manager/delete-category/{id:[0-9]+}/', Category::class . ':delete');
$route->get('/manager/settings-overview', ManagerSettings::class . ':overview');
$route->get('/manager/advertisements-overview', Advertisements::class . ':overview');
$route->get('/manager/add-advertisement', Advertisements::class . ':add');
$route->get('/manager/edit-advertisement/{id:[0-9]+}/', Advertisements::class . ':edit');
$route->get('/manager/delete-advertisement/{id:[0-9]+}/', Advertisements::class . ':delete');
$route->get('/manager/chat-overview', Chat::class . ':overview');
$route->get('/manager/view-chat/{id:[0-9]+}/{session:[0-9a-zA-Z]+}/', Chat::class . ':manager_view');
$route->get('/manager/delete-chat/{id:[0-9]+}/{session:[0-9a-zA-Z]+}/', Chat::class . ':delete')->setName('chat.delete');

$route->post('/manager/add-setting', ManagerSettings::class . ':post');
$route->post('/manager/add-chat-message', Chat::class . ':post_manager_chat_message');
$route->post('/manager/add-advertisement', Advertisements::class . ':post_add');
$route->post('/manager/edit-advertisement/{id:[0-9]+}/', Advertisements::class . ':post_edit');
$route->post('/manager/upload-image', Upload::class . ':tinymceImage');
$route->post('/manager/category-add', Category::class . ':post_add');
$route->post('/manager/support-add', Support::class . ':post_add');
$route->post('/manager/support-edit/{id:[0-9]+}/', Support::class . ':post_edit');
$route->post('/manager/add-page', Manager::class . ':post_add');
$route->post('/manager/edit-page/{pagina:[0-9]+}/', Manager::class . ':post_edit');
$route->post('/manager/blog-add', Blog::class . ':post_add');
$route->post('/manager/blog-edit/{id:[0-9]+}/', Blog::class . ':post_edit');
$route->post('/manage/add-todo', Todo::class . ':post_add');
$route->post('/manager/todo-edit/{id:[0-9]+}/', Todo::class . ':post_edit');
$route->post('/manager/upload-media', Media::class . ':post_upload');
$route->post('/manager/alt-media', Media::class . ':post_alt');
$route->post('/manager/add-link', Links::class . ':post_add');
$route->post('/manager/delete-links', Links::class . ':delete');
$route->post('/manager/edit-template/{file:[^\/]+}/', Templates::class . ':post_edit');
$route->post('/manager/settings', ManagerSettings::class . ':save');
})->add($checkManager)->add('csrf');


?>