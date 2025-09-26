<?php

namespace App\Classes;

use voku\helper\HtmlDomParser;
use Html2Text\Html2Text;
use RobotsTxtParser\RobotsTxtParser;
use RobotsTxtParser\RobotsTxtValidator;


class SiteSearch {
    protected $rooturl;
	protected $url;
	protected $spiderurl = array();
    protected $spiderseen = array();
    protected $page;
    protected $debug = false;
    protected $max = 50;
    protected $dirname = __DIR__ . "/../tmp";

public function __construct($url) {
    if (!strpos($url, 'http') === 0) {
           throw new Exception("Website url  empty cannot continue!"); 
           }
    $this->rooturl = $url;
    $this->setSpiderUrl(array('url' => '/','title' => '','text' => ''));
    }

public function spider() {
$i = 0;

foreach ($this->spiderurl as $k => &$v) {
   
    if (array_search($v['url'], array_column($this->spiderseen, 'url')) !== false) {
     $key = array_search($v['url'], array_column($this->spiderseen, 'url'));

     if ($this->debug) { print "Url seen "  . $v['url'] . "\n"; } 
     
     unset($this->spiderurl[$key]);
     continue;
     }
    if ($this->debug) { print "Goto url " . $v['url'] . "\n"; }
    $contents = array();
    /* sleep to be gentle */
    // sleep(1);
    $contents = $this->get_content(array($v['url']));

    unset($this->spiderurl[$k]);
       
       $this->setSpiderSeen(array('url' => $v['url'],'title' => $v['title'],'text' => $v['text']));


               foreach ($contents as $content) {
     
                 $this->save($k,$content['content']);
                 $this->setPage($content['content']);
                 if ($this->debug) { print "content " .  $content['content']; }
                 if ($this->debug) { print "download content from url " . $content['url'] . "\n"; }
                 $this->getLinks();
                 }
         $i++;
         if ($i > $this->max) { if ($this->debug) { print "Maximum " . $this->max . " urls reached"; } break 1; }
     }
 $this->save('indexed-urls',json_encode($this->spiderseen));

 if ($this->debug) { print "URLS indexed " . $i . "\n"; }
}

private function getLinks() {

foreach ($this->getPage()->find('a') as $a) {

    if ((preg_match('/^\\//',$a->find('a',0)->href)) || ((parse_url($a->find('a',0)->href))['host'] == (parse_url($this->url))['host'])) {
    $url = (parse_url($a->find('a',0)->href))['path'];



    if (strlen($url) < 1) { continue; }
    /* rooturl is already known */
    if ($url == "/") { continue; }
    if (str_starts_with($url, 'mailto')) { continue; }

    if ($url == $this->rooturl) { $url = "/"; }
    // images are not needed!
    $headers = get_headers($this->rooturl . $url, 1);
    if (strpos($headers['Content-Type'], 'image/') !== false) { continue; }


    if ($this->debug) { print "Found url " . $url . "\n"; } 
    if  ((array_search($url, array_column($this->spiderurl, 'url')) === false) && (array_search($url, array_column($this->spiderseen, 'url')) === false)) {
  
          $url = $this->checkUrl($url);

          if ($this->validator()->isUrlAllow($url, 'MyIndexSeoSiteBot')) {
          $this->setSpiderUrl(array('url' => $url,'title' => $a->find('a',0)->title,'text' => $a->find('a',0)->innertext));
           if ($this->debug) { print "Link toevoegen " . $url . "\n"; }
                 }
        // }
         }
      }
   }
}

private function save($filename,$txt) {

   $myfile = fopen($this->dirname . "/" . $filename . "-" . date('d-m-Y').".txt", "w");
   fwrite($myfile, $txt);
   fclose($myfile);
   return;
   }

private function getSpiderUrl() {
       return $this->spiderurl;
       }     

private function setSpiderUrl($url) {
       $this->spiderurl[] = $url;
       }

private function getSpiderSeen() {
       return $this->spiderseen;
       }     

private function setSpiderSeen($url) {
       $this->spiderseen[] = $url;
       }

private function validator() {

$content = @file_get_contents($this->rooturl . '/robots.txt');

if ($content === false) { 
       $parser = new RobotsTxtParser("User-agent: * \nAllow: /"); 
       } else {    
       $parser = new RobotsTxtParser($content);
}

$validator = new RobotsTxtValidator($parser->getRules());
return $validator;
}


private function checkUrl($url) {

      if (strpos($url, '/') !== 0) {
            return '/'. $url;    
            }
return $url;
}
      
private function get_content($urls) {
    $i = 0;
    $multiCurl = array();
    /* initiate multicurl handle */
    $mh = curl_multi_init();
       
    foreach ($urls as $url) {
        
            if (!str_starts_with($url, 'http')) {
             $url = $this->rooturl  . $url;    
            }

    $multiCurl[$i] = curl_init();
    curl_setopt($multiCurl[$i], CURLOPT_URL,$url);
    curl_setopt($multiCurl[$i], CURLOPT_HEADER, 0);
    curl_setopt($multiCurl[$i], CURLOPT_RETURNTRANSFER, 1);
    curl_multi_add_handle($mh, $multiCurl[$i]);
    $i++;
    }
    $index=null;
    do {
       curl_multi_exec($mh,$index);
    } while ($index > 0);
        foreach ($multiCurl as $k => $ch) {
        $nurls[$k]['url'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);    
        $nurls[$k]['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $nurls[$k]['total_time'] = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $nurls[$k]['starttransfer_time'] = curl_getinfo($ch, CURLINFO_PRETRANSFER_TIME);
        $nurls[$k]['content'] = curl_multi_getcontent($ch);
        curl_multi_remove_handle($mh, $ch);
    }
    /* close multicurl sessions */
    curl_multi_close($mh);
    return $nurls;
        }

private function getPage() {
    return $this->page;
    }

private function setPage($page) {
    $page = HtmlDomParser::str_get_html($page);
    $this->page = $page;
       }
}


?>

