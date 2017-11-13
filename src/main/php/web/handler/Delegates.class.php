<?php namespace web\handler;

use lang\XPClass;
use lang\ElementNotFoundException;

class Delegates implements Actions {
  private $instance;
  private $patterns= [];

  public function __construct($instance) {
    $this->instance= $instance;
    foreach (typeof($instance)->getMethods() as $method) {
      foreach ($method->getAnnotations() as $verb => $path) {
        $pattern= '#^'.$verb.':'.preg_replace('/\{([^}]+)\}/', '(?<$1>[^/]+)', $path).'$#';
        $this->patterns[$pattern]= $method;
      }
    }

    // Longest paths first
    arsort($this->patterns);
  }

  public function from($req) {
    $match= strtolower($req->method()).':'.$req->uri()->path();
    foreach ($this->patterns as $pattern => $method) {
      if ($c= preg_match($pattern, $match, $matches)) {
        for ($i= 0; $i <= $c; $i++) {
          unset($matches[$i]);
        }
        return new Delegate($this->instance, $method, $matches);
      }
    }
    throw new ElementNotFoundException('No delegate for '.$req->uri()->path());
  }
}