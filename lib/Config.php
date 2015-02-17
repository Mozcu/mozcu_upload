<?php

class Config {
    
    private $parameters;
    
    public function __construct($parameters) {
        $this->parameters = $parameters;
    }
    
    public function get($key) {
        return $this->parameters[$key];
    }
    
}
