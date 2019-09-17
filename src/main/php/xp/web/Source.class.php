<?php namespace xp\web;

use lang\ClassLoader;
use lang\IllegalArgumentException;
use web\Service;

/**
 * An service source
 *
 * @test xp://web.unittest.SourceTest
 */
class Source {
  private $service;

  /**
   * Creates a new service from a given name and environment
   *
   * @param  string $name `service[+arg[,arg[,...]]]`
   * @param  web.Environment $environment
   */
  public function __construct($name, $environment) {
    sscanf($name, '%[^+]+%s', $service, $args);

    if ('-' === $service) {
      $this->service= new ServeDocumentRootStatically($environment);
    } else {
      $cl= ClassLoader::getDefault();
      $class= is_file($service) ? $cl->loadUri($service) : $cl->loadClass($service);
      if (!$class->isSubclassOf(Service::class)) {
        throw new IllegalArgumentException($class.' is not a web.Service');
      }

      $this->service= $class->newInstance($environment, null === $args ? [] : explode(',', $args));
    }
  }

  /** @return web.Service */
  public function service() { return $this->service; }
}