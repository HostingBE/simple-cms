<?php


use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Csrf\Guard;
use Slim\Views\TwigMiddleware;
use Slim\Views\TwigExtension;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Slim\Flash\Messages;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Database\Capsule\Manager as Capsule;

use App\Views\Extensions\CsrfExtension;

use App\Controllers\Account;
use App\Controllers\Advertenties;
use App\Controllers\Blog;
use App\Controllers\Category;
use App\Controllers\Contact;
use App\Controllers\Dashboard;
use App\Controllers\Email;
use App\Controllers\Forum;
use App\Controllers\Manager\Links;
use App\Controllers\Manager;
use App\Controllers\Media;
use App\Controllers\Page;
use App\Controllers\Pages;
use App\Controllers\Partner;
use App\Controllers\Search;
use App\Controllers\Settings;
use App\Controllers\Manager\Settings as SettingsManager;
use App\Controllers\Support;
use App\Controllers\Templates;
use App\Controllers\Todo;
use App\Controllers\Login;
use App\Controllers\Logging;
use App\Controllers\Manager\Upload;
use App\Controllers\Users;


$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions(__DIR__ . '/../config/config.php');

$container = $containerBuilder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();


  $container->set('logger', function($container) {
	$settings = $container->get('settings')['logger'];
	$logger = new Monolog\Logger($settings['name']);
	$logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'],$settings['level'])); 
  return $logger;
  });

if (php_sapi_name() != "cli") {
      
$container->set('csrf', function() use ($app) {
 return new Guard($app->getResponseFactory());
 });

 // persistance mode on for ajax requests!

$container->get('csrf')->setPersistentTokenMode(1);
}
/*
* Use capsule for Sentinel
*/
$capsule = new Capsule();

$capsule->addConnection([
   'driver' => 'mysql',
   'host' => $container->get('settings')['db']['host'],
   'database' => $container->get('settings')['db']['database'],
   'username' => $container->get('settings')['db']['username'],
   'password' => $container->get('settings')['db']['password'],
   'charset' => 'utf8',
   'collation' => 'utf8_unicode_ci'

]);
$capsule->setAsGlobal();
$capsule->bootEloquent();


$container->set('locale', function($container) use ($app) {

$currentlang = substr($_SERVER['REQUEST_URI'],0,4);

if ((strpos($currentlang,"/") == 0) && (strrpos($currentlang,"/") == 3)) {
$currentlang = str_replace('/','',$currentlang);
} 
if (in_array($currentlang, $container->get('settings')['translations']['languages'])) {
return $currentlang;
          }  
$_SESSION['locale'] = $currentlang;
return "";
 });

$container->set('translator', function ($container) use ($app) {
$loader = new Illuminate\Translation\FileLoader(
  new Illuminate\Filesystem\Filesystem(), $container->get('settings')['translations']['path']
  );

$translator = new Illuminate\Translation\Translator($loader, $container->get('locale'));
$translator->setFallBack($container->get('settings')['translations']['fallback']);
    return $translator;
});


$container->set('view', function($container) {
	   $loader =  new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
         $view = new \Slim\Views\Twig($loader, [
        'debug' => $container->get('settings')['debug'],
        'cache' => __DIR__. '/../cache'
    ]);
    $view->addExtension(new \Twig\Extension\DebugExtension());
   
    return $view;	
});

if (php_sapi_name() != "cli") {
$container->get('view')->addExtension(
   new CsrfExtension($container->get('csrf'),$container->get('locale'))
);
}

$container->get('view')->addExtension(new App\Views\Extensions\TranslationExtension($container->get('translator')));

$app->add(TwigMiddleware::createFromContainer($app));

$user = Sentinel::getUser();
if ($user) {
          $roles = $user->roles()->get();
    $container->get("view")->getEnvironment()->addGlobal('user', $user);
    $container->get("view")->getEnvironment()->addGlobal('role', $roles[0]->name);
  }


$container->set('db', function($container) {
       try {
       $db = new PDO('mysql:host='.$container->get('settings')['db']['host'].';dbname='.$container->get('settings')['db']['database'],$container->get('settings')['db']['username'],$container->get('settings')['db']['password']);
       } catch (\Exception $e) {
       $container->get('logger')->error("dbase connection error: " .  $e->getMessage());
       echo 'Error: ',  $e->getMessage() , "\n";
       exit;
       }
       return $db;
});

$container->set('flash', function() {
	return new Messages();
});


/*
* configuration items from database
*/
$container->set('sitesettings', function($container) use ($app) {

$sql = $container->get('db')->prepare("SELECT setting,value FROM website_settings");
$sql->execute();
$settingstemp = $sql->fetchALL(PDO::FETCH_OBJ);
$results = array_combine(array_column($settingstemp, 'setting'), array_column($settingstemp, 'value'));
return $results;
});


if ($container->get('locale')) {
$container->get("view")->getEnvironment()->addGlobal('locale_url', $container->get('sitesettings')['url']."/".$container->get('locale')); 
} else {
$container->get("view")->getEnvironment()->addGlobal('locale_url', $container->get('sitesettings')['url']);     
}
$container->get("view")->getEnvironment()->addGlobal('url', $container->get('sitesettings')['url']);   
$container->get("view")->getEnvironment()->addGlobal('sitename',$container->get('sitesettings')['sitename']);
$container->get("view")->getEnvironment()->addGlobal('version',$container->get('settings')['version']);
$container->get("view")->getEnvironment()->addGlobal('advertenties',$container->get('sitesettings')['advertenties']);
$container->get("view")->getEnvironment()->addGlobal('locale',$container->get('locale'));
$container->get("view")->getEnvironment()->addGlobal('multilanguage',$container->get('sitesettings')['multilanguage']);
$container->get("view")->getEnvironment()->addGlobal('htmleditor',$container->get('sitesettings')['htmleditor']);
$container->get("view")->getEnvironment()->addGlobal('disableforum',$container->get('sitesettings')['disableforum']);
$container->get("view")->getEnvironment()->addGlobal('disablesupport',$container->get('sitesettings')['disablesupport']);

$container->set('mail', function () {

    $mail = new PHPmailer();
    $mail->isSMTP();
    $mail->Host = 'localhost';
    $mail->SMTPAuth = false;
    $mail->SMTPAutoTLS = false; 
    $mail->Port = 25;
    $mail->isHTML(false);
    return $mail;
});

/*
* management ip excluding from statistics and chat
*/
$management_ip = false;
if (get_client_ip() == $container->get('sitesettings')['management_ip']) { $management_ip = true; }
$container->get("view")->getEnvironment()->addGlobal('management_ip',$management_ip);

/*
* blogs for footer
*/
$sql = $container->get('db')->prepare("SELECT a.id,a.title,a.user,a.image,substr(a.content,1,200) as content,DATE_FORMAT(a.date,'%d-%m-%Y') AS datum,b.naam as categorienaam,CONCAT(c.naam,'.',c.extentie) as media,c.naam as imagename,c.alt,(SELECT COUNT(*) as aantal from blog_reacties where blog=a.id and status='a') AS reacties from blog AS a LEFT JOIN categorie b ON b.id=a.category LEFT JOIN media c ON c.id=a.image WHERE a.publish='y' AND a.publishdate <= now() ORDER BY a.id desc limit 2");
$sql->execute();
$footer_blogs = $sql->fetchALL(PDO::FETCH_OBJ);

/*
* menu for header and footer
*/
$sql = $container->get('db')->prepare("SELECT a.name,a.title,a.url,b.naam FROM links AS a LEFT JOIN categorie AS b ON b.id=a.category");
$sql->execute();
$linksobj = $sql->fetchALL(PDO::FETCH_OBJ);
$headerlinks = array();
$footerlinks = array();

foreach ($linksobj as $link) {
            if ($link->naam == "header") {
            $headerlinks[$link->naam][] = $link; 
            }
            if ($link->naam != "header") {
            $footerlinks[$link->naam][] = $link; 
            }            
}


$container->get("view")->getEnvironment()->addGlobal('footer_blogs', (array) $footer_blogs); 
$container->get("view")->getEnvironment()->addGlobal('headerlinks', (array) $headerlinks);
$container->get("view")->getEnvironment()->addGlobal('footerlinks', (array) $footerlinks);

$container->set(Page::class, function($container) {
return new Page(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('locale'),
      $container->get('settings')['translations']['fallback']
      );

});

$container->set(Pages::class, function($container) {
return new Pages(
      $container->get('view'),
      $container->get('db')
      );

});

$container->set(Category::class, function($container) {
return new Category(
      $container->get('view'),
      $container->get('db')
      );

});

$container->set(Templates::class, function($container) {
return new Templates(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash')
      );

});

$container->set(SettingsManager::class, function($container) {
return new SettingsManager(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings') 
      );

});

$container->set(Links::class, function($container) {
return new Links(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings')
      );

});

$container->set(Media::class, function($container) {
return new Media(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings') 
      );

});

$container->set(Settings::class, function($container) {
return new Settings(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings')  
      );

});

$container->set(Blog::class, function($container) {
return new Blog(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings')  
      );

});

$container->set(Login::class, function($container) {
return new Login(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('logger'),   
      $container->get('sitesettings'),
      $container->get('locale'),   
      $container->get('translator')  
      );

});

$container->set(Users::class, function($container) {
return new Users(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings')    
      );

});
$container->set(Account::class, function($container) {
return new Account(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings'),   
      $container->get('locale') 
      );

});

$container->set(Contact::class, function($container) {
return new Contact(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings'),
      $container->get('locale')   
      );

});
$container->set(Dashboard::class, function($container) {
return new Dashboard(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings')  
      );

});
$container->set(Partner::class, function($container) {
return new Partner(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings')  
      );

});
$container->set(Manager::class, function($container) {
return new Manager(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings'),
      $container->get('settings')['translations']['languages']      
      );

});
$container->set(Todo::class, function($container) {
return new Todo(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings')  
      );

});
$container->set(Logging::class, function($container) {
return new Logging(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings')  
      );

});
$container->set(Forum::class, function($container) {
return new Forum(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings'),
      $container->get('locale')     
      );

});
$container->set(Support::class, function($container) {
return new Support(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings'),  
      $container->get('locale')  
      );

});
$container->set(Search::class, function($container) {
return new Search(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings'),
      $container->get('locale'),
      $container->get('translator')          
      );

});
$container->set(Upload::class, function($container) {
return new Upload(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings'),
      $container->get('locale')    
      );

});
$container->set(Email::class, function($container) {
return new Email(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings')
      );

});
$container->set(Advertenties::class, function($container) {
return new Advertenties(
      $container->get('view'),
      $container->get('db'),
      $container->get('flash'),
      $container->get('mail'),
      $container->get('logger'),   
      $container->get('sitesettings'),
      $container->get('locale'),
      $container->get('translator') 
      );

});
?>
