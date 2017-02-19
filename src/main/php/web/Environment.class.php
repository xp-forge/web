<?php namespace web;

use lang\ElementNotFoundException;
use util\CompositeProperties;
use util\Objects;
use util\PropertySource;
use util\FilesystemPropertySource;
use util\ResourcePropertySource;

class Environment {
  private $profile, $webroot, $docroot;
  private $sources= [];

  public function __construct($profile, $webroot, $docroot, $config) {
    $this->profile= $profile;
    $this->webroot= $webroot;
    $this->docroot= $docroot;
    foreach ($config as $source) {
      if ($source instanceof PropertySource) {
        $this->sources[]= $source;
      } else if (is_dir($source)) {
        $this->sources[]= new FilesystemPropertySource($source);
      } else {
        $this->sources[]= new ResourcePropertySource($source);
      }
    }
  }

  public function profile() { return $this->profile; }

  public function webroot() { return $this->webroot; }

  public function docroot() { return $this->docroot; }

  /**
   * Gets properties
   *
   * @param  string $name
   * @return util.PropertyAccess
   * @throws lang.ElementNotFoundException
   */
  public function properties($name) {
    $found= [];
    foreach ($this->sources as $source) {
      if ($source->provides($name)) {
        $found[]= $source->fetch($name);
      }
    }

    switch (sizeof($found)) {
      case 1: return $found[0];
      case 0: throw new ElementNotFoundException(sprintf(
        'Cannot find properties "%s" in any of %s',
        $name,
        Objects::stringOf($this->sources)
      ));
      default: return new CompositeProperties($found);
    }
  }
}