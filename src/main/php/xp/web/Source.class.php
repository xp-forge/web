<?php namespace xp\web;

use lang\XPClass;

class Source {
  private $application;

  /**
   * Creates a new application from a given name and environment
   *
   * @param  string $name `application[+filter[,filter[,...]]]`
   * @param  web.Environment $environment
   */
  public function __construct($name, $environment) {
    sscanf($name, '%[^+]+%s', $application, $filters);

    if ('-' === $application) {
      $this->application= new ServeDocumentRootStatically($environment);
    } else {
      $this->application= XPClass::forName($application)->newInstance($environment);
    }

    if ($filters) {
      $this->application->install(array_map(
        function($filter) { return XPClass::forName($filter)->newInstance(); },
        explode(',', $filters)
      ));
    }
  }

  /** @return web.Application */
  public function application() { return $this->application; }
}