<?php


namespace App\Views\Extensions;

use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use Illuminate\Translation\Translator;

class TranslationExtension extends AbstractExtension {

protected $translator;


public function __construct(Translator $translator) {
	
	$this->translator = $translator;
    
    }

public function getFunctions() {

   return [
       new TwigFunction('trans', [$this, 'trans']),
       new TwigFunction('trans_choice', [$this, 'trans_choice'])
   ];

   }

public function trans($key,array $replace = []) {

return $this->translator->get($key,$replace);
} 

public function trans_choice($key, $count,array $replace = []) {

return $this->translator->choice($key,$count,$replace);
} 

}


?>