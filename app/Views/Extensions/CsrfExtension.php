<?php

namespace App\Views\Extensions;


use Slim\Csrf\Guard;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;



class  CsrfExtension extends AbstractExtension {

protected $csrf;
protected $locale;

public function __construct(Guard $csrf,string $locale) {

$this->csrf = $csrf;
$this->locale = $locale;
}


public function getFunctions() {
	
	return [
	new TwigFunction('csrf',[$this,'csrf'])
	];
}

public function csrf() {
	
	return '
	<input type="hidden" name="locale" value="'.$this->locale.'">

	<input type="hidden" name="'. $this->csrf->getTokenNameKey() .'" value="'. $this->csrf->getTokenName() .'">
    <input type="hidden" name="'. $this->csrf->getTokenValueKey() .'" value="'. $this->csrf->getTokenValue() .'">
	';
   }

}


?>