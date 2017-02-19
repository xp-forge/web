<?php namespace xp\web;

use lang\XPClass;

class Source {
  private $application;

  public function __construct($name, $environment) {
    if ('-' === $name) {
      $this->application= new ServeDocumentRootStatically($environment);
    } else {
      $this->application= XPClass::forName($name)->newInstance($environment);
    }
  }

  public function application() { return $this->application; }
}