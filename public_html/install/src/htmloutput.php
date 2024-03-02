<?php

namespace Install;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class HtmlOutput {

    private $config;

public function __construct() {
    $this->config = $this->getConfig();
    }


private function getConfig($yamlfile = __DIR__ . '/../config/config.yaml') {
    
    try {
        $cfg = Yaml::parseFile($yamlfile);
    } catch (ParseException $exception) {
        printf('Unable to parse the YAML string: %s', $exception->getMessage());
    }

    return (array) $cfg;
    }

public function getPHPversion() {
if (phpversion() < $this->config['phpversion']) {
    return array('version' => phpversion(),'check' => false,'message' => 'your php version ' . phpversion() . ' does not meet the standard ' . $this->config['phpversion']);
    }
if (phpversion() >= $this->config['phpversion']) {
    return array('version' => phpversion(),'check' => true,'message' => 'your php version ' . phpversion() . ' is good to go at least ' . $this->config['phpversion']);
    }    
return array();
}


public function getName() {
    return $this->config['name'];    
    }

public function getTitle() {
    return $this->config['title'];       
    }

public function getModules() {
    return $this->config['modules'];       
    }

public function getDirectories() {
    return $this->config['directories'];       
    }


public function getMenu() {
    $sort = array_column($this->config['menu'], 'sort');
    array_multisort($sort, SORT_ASC, $this->config['menu']);
    return $this->config['menu'];
    }
}
?>