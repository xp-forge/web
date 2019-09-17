<?php namespace web;

use lang\XPClass;
use web\protocol\Http;

/**
 * Application is at the heart at every web project.
 *
 * @test  xp://web.unittest.ApplicationTest
 */
abstract class Application extends Service {
  private $routing= null;
  private $filters= [];

  /**
   * Creates a new web application inside a given environment
   *
   * @param  web.Environment $environment
   * @param  string[] $filters Names of filter classes
   */
  public function __construct(Environment $environment, $filters= []) {
    parent::__construct($environment);
    foreach ($filters as $filter) {
      $this->filters[]= XPClass::forName($filter)->newInstance();
    }
  }

  /**
   * Returns routing, lazily initialized
   *
   * @return web.Routing
   */
  public final function routing() {
    if (null === $this->routing) {
      if ($this->filters) {
        $this->routing= Routing::cast(new Filters($this->filters, $this->routes()), true);
      } else {
        $this->routing= Routing::cast($this->routes(), true);
      }
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
   * Applications can be accessed via HTTP protocol on a given server instance
   *
   * @param  peer.server.Server $server
   * @return web.protocol.Protocol
   */
  public function serve($server) {
    return new Http($this, $this->environment->logging());
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