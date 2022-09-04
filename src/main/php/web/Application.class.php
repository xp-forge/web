<?php namespace web;

/**
 * Application is at the heart at every web project.
 *
 * @test  xp://web.unittest.ApplicationTest
 */
abstract class Application implements \lang\Value {
  private $routing= null;
  protected $environment;

  /**
   * Creates a new web application inside a given environment
   *
   * @param  web.Environment $environment
   */
  public function __construct(Environment $environment) {
    $this->environment= $environment;
  }

  /** @return web.Environment */
  public function environment() { return $this->environment; }

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
    $this->routing= new Filters($filters, $this->routing());
  }

  /**
   * Service delegates to the routing, calling its `handle()` method.
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @return var
   */
  public function service($request, $response) {
    return $this->routing()->handle($request, $response);
  }

  /** @return string */
  public function toString() { return nameof($this).'('.$this->environment->docroot().')'; }

  /** @return string */
  public function hashCode() { return spl_object_hash($this); }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value === $this ? 0 : 1;
  }
}