<?php namespace web;

/**
 * Application is at the heart at every web project.
 *
 * @test  xp://web.unittest.ApplicationTest
 */
abstract class Application {
  private $routing;

  /**
   * Returns routing, lazily initialized
   *
   * @return web.Routing
   */
  public final function routing() {
    if (null === $this->routing) {
      $this->routing= Routing::for($this->routes());
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
  protected abstract function routes();

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