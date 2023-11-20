<?php namespace xp\web;

use lang\{ClassLoader, XPClass, IllegalArgumentException, ClassLoadingException};
use web\Application;

/**
 * An application source
 *
 * @test  web.unittest.SourceTest
 */
class Source {
  private $application;

  /**
   * Creates a new instance
   *
   * @param  string $application
   * @param  web.Environment $environment
   * @return web.Application
   * @throws lang.IllegalArgumentException
   */
  private function newInstance($application, $environment) {
    if ('-' === $application) return new ServeDocumentRootStatically($environment);

    $cl= ClassLoader::getDefault();
    try {
      if (is_file($application)) {
        $class= $cl->loadUri($application);
      } else if ($application[0] < 'a' || false !== strpos($application, '.')) {
        $class= $cl->loadClass($application);
      } else {
        $class= $cl->loadClass("xp.{$application}.Web");
      }
    } catch (ClassLoadingException $e) {
      throw new IllegalArgumentException('Cannot load class '.$application, $e);
    }

    if (!$class->isSubclassOf(Application::class)) {
      throw new IllegalArgumentException($class->getName().' is not a web.Application');
    }

    return $class->newInstance($environment);
  }

  /**
   * Creates a new application from a given name and environment
   *
   * @param  string $name `application[+filter[,filter[,...]]]`
   * @param  web.Environment $environment
   */
  public function __construct($name, $environment) {
    sscanf($name, '%[^+]+%s', $application, $filters);
    $this->application= $this->newInstance($application, $environment);

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