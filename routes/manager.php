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
use App\Controllers\Manager\BlogComments;
use App\Controllers\Manager\Category;
use App\Controllers\Chat;
use App\Controllers\Manager\Contacts;
use App\Controllers\Dashboard;
use App\Controllers\Manager\Events;
use App\Controllers\Forum;
use App\Controllers\Manager\Keywords;
use App\Controllers\Manager\Links;
use App\Controllers\Manager\Manager;
use App\Controllers\Manager\Media;
use App\Controllers\Page;
use App\Controllers\Settings;
use App\Controllers\Manager\Settings as ManagerSettings;
use App\Controllers\Support;
use App\Controllers\Manager\SupportComments;
use App\Controllers\Templates;
use App\Controllers\Manager\Todo;
use App\Controllers\Manager\Logging;
use App\Controllers\Manager\Upload;
use App\Controllers\Manager\Users;

$checkManager = new \App\Middleware\CheckManager();

$app->group('/manager', function($route) {
$route->get('/dashboard', Dashboard::class . ':manager_overview')->setName('dashboard.manager_overview');
$route->get('/page-add', Manager::class . ':add')->setName('manager.add');
$route->get('/edit-page/{pagina:[0-9]+}/', Manager::class . ':edit')->setName('manager.edit');
$route->get('/add-setting', ManagerSettings::class . ':add')->setName('managersettings.add');
$route->get('/pages-overview', Manager::class . ':overview')->setName('manager.overview');
$route->get('/delete-page/{pagina:[0-9]+}/', Manager::class . ':delete')->setName('manager.delete');
$route->get('/blog-add', Blog::class . ':add')->setName('blog.add');
$route->get('/blogs-overview', Blog::class . ':manager_overview')->setName('blog.manager_overview');
$route->get('/links-overview', Links::class . ':overview')->setName('links.overview');
$route->get('/keywords-overview', Keywords::class . ':overview')->setName('keywords.overview');
$route->get('/category-overview', Category::class . ':overview')->setName('category.overview');
$route->get('/blog-edit/{id:[0-9]+}/', Blog::class . ':edit')->setName('blog.edit');
$route->get('/delete-blog/{id:[0-9]+}/', Blog::class . ':delete')->setName('blog.delete');
$route->get('/todo-overview', Todo::class . ':overview');
$route->get('/templates-overview', Templates::class . ':overview');
$route->get('/edit-template/{file:[^\/]+}/', Templates::class . ':edit');
$route->get('/add-template', Templates::class . ':add');
$route->get('/todo-edit/{id:[^\/]+}/', Todo::class . ':edit');
$route->get('/delete-todo/{id:[0-9]+}/', Todo::class . ':delete');
$route->get('/view-logging', Logging::class . ':view')->setName('logging.view');
$route->get('/do-bekijk-logging/{file}/', Logging::class . ':post');
$route->get('/media-overview', Media::class . ':overview')->setName('media.overview');
$route->get('/delete-media/{id:[0-9]+}/', Media::class . ':delete')->setName('media.delete');
$route->get('/add-support', Support::class . ':add')->setName('support.add');
$route->get('/support-overview', Support::class . ':manager_overview')->setName('support.manager_overview');
$route->get('/support-comments', SupportComments::class . ':overview')->setName('supportcomments.overview');
$route->get('/blog-comments', BlogComments::class . ':overview')->setName('blogcomments.overview');
$route->get('/support-edit/{id:[0-9]+}/', Support::class . ':edit')->setName('support.edit');
$route->get('/delete-support/{id:[0-9]+}/', Support::class . ':delete')->setName('support.delete');
$route->get('/users-overview', Users::class . ':overview')->setName('users.overview');
$route->get('/delete-user/{id:[0-9]+}/', Users::class . ':delete');
$route->get('/view-user/{id:[0-9]+}/', Users::class . ':view');
$route->get('/delete-category/{id:[0-9]+}/', Category::class . ':delete');
$route->get('/settings-overview', ManagerSettings::class . ':overview');
$route->get('/advertisements-overview', Advertisements::class . ':overview');
$route->get('/add-advertisement', Advertisements::class . ':add');
$route->get('/edit-advertisement/{id:[0-9]+}/', Advertisements::class . ':edit');
$route->get('/delete-advertisement/{id:[0-9]+}/', Advertisements::class . ':delete');
$route->get('/chat-overview', Chat::class . ':overview');
$route->get('/contact-overview', Contacts::class . ':overview');
$route->get('/view-chat/{id:[0-9]+}/{session:[0-9a-zA-Z]+}/', Chat::class . ':manager_view');
$route->get('/delete-chat/{id:[0-9]+}/{session:[0-9a-zA-Z]+}/', Chat::class . ':delete')->setName('chat.delete');
$route->get('/support-comment-delete/{id:[0-9]+}/{code:[0-9a-zA-Z]+}/', SupportComments::class . ':delete')->setName('supportcomments.delete');
$route->get('/support-comment-edit/{id:[0-9]+}/{code:[0-9a-zA-Z]+}/', SupportComments::class . ':edit')->setName('supportcomments.edit');
$route->get('/delete-contact/{id:[0-9]+}/{code:[0-9a-zA-Z]+}/', Contacts::class . ':delete')->setName('contacts.delete');
$route->get('/edit-contact/{id:[0-9]+}/{code:[0-9a-zA-Z]+}/', Contacts::class . ':edit')->setName('contacts.edit');
$route->get('/blog-comment-delete/{id:[0-9]+}/{code:[0-9a-zA-Z]+}/', BlogComments::class . ':delete')->setName('blogcomments.delete');
$route->get('/blog-comment-edit/{id:[0-9]+}/{code:[0-9a-zA-Z]+}/', BlogComments::class . ':edit')->setName('blogcomments.edit');
$route->get('/delete-keyword/{id:[0-9]+}/{code:[0-9a-zA-Z]+}/', Keywords::class . ':delete')->setName('keywords.delete');

$route->post('/edit-blog-comment/{id:[0-9]+}/{code:[0-9a-zA-Z]+}/', BlogComments::class . ':post');
$route->post('/edit-support-comment/{id:[0-9]+}/{code:[0-9a-zA-Z]+}/', SupportComments::class . ':post');
$route->post('/overview-events', Events::class . ':post');
$route->post('/add-template', Templates::class . ':post_add_template');
$route->post('/add-setting', ManagerSettings::class . ':post');
$route->post('/add-chat-message', Chat::class . ':post_manager_chat_message');
$route->post('/add-advertisement', Advertisements::class . ':post_add');
$route->post('/edit-advertisement/{id:[0-9]+}/', Advertisements::class . ':post_edit');
$route->post('/upload-image', Upload::class . ':tinymceImage');
$route->post('/category-add', Category::class . ':post_add');
$route->post('/support-add', Support::class . ':post_add');
$route->post('/support-edit/{id:[0-9]+}/', Support::class . ':post_edit');
$route->post('/add-page', Manager::class . ':post_add');
$route->post('/edit-page/{pagina:[0-9]+}/', Manager::class . ':post_edit');
$route->post('/blog-add', Blog::class . ':post_add');
$route->post('/blog-edit/{id:[0-9]+}/', Blog::class . ':post_edit');
$route->post('/manage/add-todo', Todo::class . ':post_add');
$route->post('/todo-edit/{id:[0-9]+}/', Todo::class . ':post_edit');
$route->post('/upload-media', Media::class . ':post_upload');
$route->post('/change-filename', Media::class . ':post_name');
$route->post('/alt-media', Media::class . ':post_alt');
$route->post('/add-link', Links::class . ':post_add');
$route->post('/add-keyword', Keywords::class . ':post_add');
$route->post('/delete-links', Links::class . ':delete');
$route->post('/edit-template/{file:[^\/]+}/', Templates::class . ':post_edit');
$route->post('/settings', ManagerSettings::class . ':save');
})->add($checkManager)->add('csrf');


?>