<?php namespace xp\web;

use lang\ClassLoader;
use lang\XPClass;

/**
 * An application source
 *
 * @test xp://web.unittest.SourceTest
 */
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
    } else if (is_file($application)) {
      $this->application= ClassLoader::getDefault()->loadUri($application)->newInstance($environment);
    } else {
      $this->application= ClassLoader::getDefault()->loadClass($application)->newInstance($environment);
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