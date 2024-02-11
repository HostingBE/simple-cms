<?php

namespace App\Console\Commands;

use PDO; 
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

require(__DIR__."/../../../Classes/SiteSearch.class.php");
require(__DIR__."/../../../Classes/WebsiteParser.class.php");

use App\Classes\SiteSearch;
use App\Classes\WebsiteParser;

class SearchEngine extends Command {
    protected $db;
    protected $settings;
    protected $logger;
    protected $mail;
    protected $view;
    protected $dirname = __DIR__ . "/../../../tmp";
    protected $lang = "en";



	 public function __construct($db, $settings, $logger, $mail, $view) {
        
        parent::__construct();
        
        $this->db = $db;
        $this->settings = $settings;
        $this->logger = $logger;    
        $this->mail = $mail;
        $this->view = $view; 
        }

        protected function configure() {
        $this->setName('search-engine')
            ->setDescription('Local site search engine to index content from website!')
            ->addArgument('cmd', InputArgument::REQUIRED, 'Welk commando wil je laten uitvoeren?')
            ->addArgument('url', InputArgument::OPTIONAL, 'Welk commando wil je laten uitvoeren?')
                ->setHelp('Momenteel nog geen argumenten beschikbaar');
             }


    protected function execute(InputInterface $input, OutputInterface $output)  :int  {
    
    $cmd = $input->getArgument('cmd');

    if ($cmd == "import-index") {
    $this->logger->warning("Ik ga het commando " . $cmd . " uitvoeren");
    if ($this->import_index($input,$output) === 0) {
        return 0;
    }
        return 1;
    }        
       
    if ($cmd == "index-website") {
    $this->logger->warning("Ik ga het commando " . $cmd . " uitvoeren");
    if ($this->index_website($input,$output) === 0) {
        return 0;
    }
        return 1;
    }    

    $output->writeln("Helaas dit command wordt niet ondersteund!");
    return 0;
    }    

    protected function import_index(InputInterface $input, OutputInterface $output) {
    print "Beginnen met de import" . "\n";

    $sql = $this->db->prepare("SELECT word FROM stopwords WHERE language=:lang");
    $sql->bindparam(":lang",$this->lang,PDO::PARAM_STR);
    $sql->execute();
    $stopwords = $sql->fetchALL(PDO::FETCH_COLUMN, 0);
  
    if (file_exists($this->dirname . "/indexed-urls-".date('d-m-Y').".txt")) {
    $json  = file_get_contents($this->dirname . "/indexed-urls-".date('d-m-Y').".txt",true);
    $urls = json_decode($json,true);
    };
    $i = 0;

    $sql = $this->db->prepare("TRUNCATE search_urls");
    $sql->execute();
    $sql = $this->db->prepare("TRUNCATE search_words");
    $sql->execute(); 


    foreach ($urls as $url) {
       print "Afhandelen van url " . $url['url'] ."\n";

       if (file_exists($this->dirname . "/". $i ."-". date('d-m-Y').".txt")) {
           $page = file_get_contents($this->dirname . "/". $i ."-". date('d-m-Y').".txt");

           $parser = new WebsiteParser($page);
           $parser->setStopWords($stopwords);
           $words = $parser->getWords();
           $metatags = $parser->getMetaTags();
           $title = $parser->getTitle();
  
           $sql = $this->db->prepare("INSERT INTO search_urls (id,url,title,description,keywords,date) VALUES('',:url,:title,:description,:keywords,now())");
           $sql->bindparam(":url",$url['url'], PDO::PARAM_STR);
           $sql->bindparam(":title",$title,PDO::PARAM_STR);
           $sql->bindparam(":description",$metatags['description'],PDO::PARAM_STR);
           $sql->bindparam(":keywords",$metatags['keywords'],PDO::PARAM_STR);
           $sql->execute();
           $urlid = $this->db->lastinsertid();
           
           $sql = $this->db->prepare("INSERT INTO search_words (id,word,density,url) VALUES('',:word,:density,:url)");
           foreach ($words as $word => $density) { 
           $sql->bindparam(":word",$word, PDO::PARAM_STR);
           $sql->bindparam(":density",$density,PDO::PARAM_INT);
           $sql->bindparam(":url",$urlid,PDO::PARAM_INT);
           $sql->execute();
           }
          unlink($this->dirname . "/". $i ."-". date('d-m-Y').".txt");  
         }
     $i++;
     }
     unlink($this->dirname . "/indexed-urls-".date('d-m-Y').".txt");

    return 0;
    }


    protected function index_website(InputInterface $input, OutputInterface $output) {
     $url = $input->getArgument('url');

    $robot = new SiteSearch($url);
    $robot->spider();
    return 0; 
    }

}

?>