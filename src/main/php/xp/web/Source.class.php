<?php namespace xp\web;

use lang\XPClass;

class Source {
  private $application;

  /**
   * Creates a new application from a given name and environment
   *
   * @param  string $name
   * @param  web.Environment $environment
   */
  public function __construct($name, $environment) {
    if ('-' === $name) {
      $this->application= new ServeDocumentRootStatically($environment);
    } else {
      $this->application= XPClass::forName($name)->newInstance($environment);
    }
  }

  /** @return web.Application */
  public function application() { return $this->application; }
}