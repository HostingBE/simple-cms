<?php

namespace App\Classes;

use voku\helper\HtmlDomParser;
use Html2Text\Html2Text;

class WebsiteParser {

protected $page;
protected $stopwords;

public function __construct($page) {
    if (strlen($page) < 10) {
           throw new Exception("Website content  empty cannot continue!"); 
           }
    $this->page = $page;
    }


/*
* find title in a HTML website
*/
public function getTitle() {
$page = HtmlDomParser::str_get_html($this->page);
$title = $page->find('title', 0)->innertext ?: '';
return $title;    
}

/*
* find meta name content in page
*/
public function getMetaTags() {

$page = HtmlDomParser::str_get_html($this->page);

foreach ($page->find('meta') as $meta) {
    if ($meta->hasAttribute('content')) {
        $meta_data[$meta->getAttribute('name')] = $meta->getAttribute('content');
     }
  }           
    return $meta_data;  
}


public function get_text() {
    $html = new Html2Text($this->page); 
    $text = strtolower($html->getText());
    return $text;
    }

public function getWords() {
  $words = $wordsarray = array();
  $text = $this->cleanSpaces($this->get_text());
  $text = $this->decode($text);
  $text = $this->cleanNumerics($text);
  $words = explode(' ',$text);
  foreach ($words as $word) {
        $word = strtolower($word);

        if ((strlen($word) >= 3) && (!in_array($word,$this->getStopWords()))) {
        $wordsarray[] = $word;
        }
  }

  $words2 = array_count_values($wordsarray);
  arsort($words2, SORT_NUMERIC);
  return $words2;
  }


private function getStopWords() {
        return $this->stopwords;
        }

public function setStopWords($stopwords) {
        $this->stopwords = $stopwords;
        }        

private function decode($string) {
        return html_entity_decode($string, ENT_QUOTES | ENT_XML1 | ENT_HTML5, 'UTF-8');
        }

private function cleanNumerics($string) {
        $string = preg_replace('/[^a-zA-Z]/',' ',$string);
        return $string;
        }        

private function cleanSpaces($string) {
    $string = preg_replace('/\v(?:[\v\h]+)/',' ',$string);
    $string = ltrim($string);
    $string = rtrim($string);
    return $string;
   }  
}


?>