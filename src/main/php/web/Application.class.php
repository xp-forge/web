<?php namespace web;

use web\protocol\Http;

/**
 * Application is at the heart at every web project.
 *
 * @test  xp://web.unittest.ApplicationTest
 */
abstract class Application extends Service {
  private $routing= null;

  /**
   * Returns routing, lazily initialized
   *
   * @return web.Routing
   */
  public final function routing() {
    if (null === $this->routing) {
      $this->routing= Routing::cast($this->routes(), true);
    }
    return $this->routing;    
  }

  /**
   * Returns this application's routes, which are either a `Routing`
   * instance or a map of paths to routing targets.
   *
   * _Overwrite this in your implementation!_
   *
   * @return web.Routing|[:var]
   */
  public abstract function routes();

  /**
   * Installs global filters
   *
   * @param  web.Filter[] $filters
   * @return void
   */
  public function install($filters) {
    $this->routing= Routing::cast(new Filters($filters, $this->routing()), true);
  }

  public function serve($server, $environment) {
    return new Http($this, $environment->logging());
  }

  /**
   * Service delegates to the routing, calling its `service()` method.
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @return void
   */
  public function service($request, $response) {
    $this->routing()->service($request, $response);
  }
}