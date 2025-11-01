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
foreach ($this->keywords as $name => $link) {
    $lookup[strtolower($link->keyword)] = $link->link;
    $regexNeedles[] = preg_quote($link->keyword, '~');
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
            $a->setAttribute('href', 'https://' . $lookup[$fragmentLower]);
            $a->setAttribute('title', $fragment);
            $a->setAttribute('data-bs-toggle', 'tooltip');
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
}

?>