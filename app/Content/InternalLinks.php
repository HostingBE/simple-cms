<?php

/**
 * @author Constan van Suchtelen van de Haere <constan@hostingbe.com>
 * @copyright 2023 HostingBE
 */

namespace App\Content;

class InternalLinks {

protected $html;
protected $keywords;
protected $max = 3;

public function __construct(string $html, array $keywords) {
	$this->html = $html;
	$this->keywords = $keywords;
    }

/**
* parse the HTML and generate the links returning string of html
*/
public function generateLinks() :string {

$counter = 0;
$dom = new \DOMDocument();
$dom->loadHTML($this->gethtml());
$dom->removeChild($dom->doctype); 

$xpath = new \DOMXPath($dom);

$lookup = [];
$regexNeedles = [];
foreach ($this->getUniqueKeywrds() as $name => $link) {
    $lookup[strtolower($link['keyword'])] = $link['link'];
    $regexNeedles[] = preg_quote($link['keyword'], '~');
}


$pattern = '~\b(' . implode('|', $regexNeedles) . ')\b~i' ;

foreach($xpath->query('//*[not(self::img or self::a)][(self::p|self::blockquote)]/text()') as $textNode) {
    $newNodes = [];
    $hasReplacement = false;
    foreach (preg_split($pattern, $textNode->nodeValue, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) as $fragment) {


        $fragmentLower = strtolower($fragment);
        if (isset($lookup[$fragmentLower])) {
            $hasReplacement = true;
            $a = $dom->createElement('a');
            $a->setAttribute('href', $lookup[$fragmentLower]);
            $a->setAttribute('title', $fragment);
            $a->nodeValue = $fragment;
            $newNodes[] = $a;
        } else {
            $newNodes[] = $dom->createTextNode($fragment);
        }
    }
    if ($hasReplacement) {
        $newFragment = $dom->createDocumentFragment();
        foreach ($newNodes as $newNode) {
            $newFragment->appendChild($newNode);
        }
        $textNode->parentNode->replaceChild($newFragment, $textNode);
      }
  }
return $this->plainhtml($dom->saveHTML());
}

protected function plainhtml($html) {
    $html = str_replace('<html><body>', '', $html);
    $html = str_replace('</body></html>', '', $html);
    return $html;
}

protected function sethtml($html) {
	$this->html = $html;
    }

protected function gethtml() {
	return $this->html;
   }

protected function getUniqueKeywrds() :array {
$seen = $arr = array();

foreach ($this->adjustKeywords() as $keyword) {
        if (isset($seen[$keyword['link']])) {
        continue;    
        }
        $seen[$keyword['link']] = 1;
$arr[] = $keyword;
}

shuffle($arr);

return array_slice($arr, 0, $this->max);
}

/**
* @array keywords = array('keyword' => keyword, 'link'=> link) 
*
*/
protected function adjustKeywords() :array {
     $keywords = array();

     foreach ($this->keywords as $keyword) {
     		$a = explode(',',$keyword->tags);
                 foreach ($a as $b) {
                     $c = trim($b);
                      $keywords[] = array('keyword' => $c, 'link' => $keyword->link);
                 }
            }
  
        return $keywords;
        }
  }

?>