<?php namespace web;

use Closure;
use lang\Value;

/**
 * Application is at the heart at every web project.
 *
 * @test  web.unittest.ApplicationTest
 */
abstract class Application implements Value {
  private $routing= null;
  private $filters= [];
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
   * Returns routing handler, lazily initialized
   *
   * @return web.Handler
   */
  public final function routing() {
    if (null === $this->routing) {
      $routing= Routing::cast($this->routes(), true);
      $this->routing= $this->filters ? new Filters($this->filters, $routing) : $routing;
    }
    return $this->routing;    
  }

  /**
   * Initializes this application, being run once when the server starts.
   * Empty in this default implementation, overwrite in subclasses.
   *
   * @return void
   */
  public function initialize() {
    // Empty
  }

  /**
   * Returns this application's routes, which are either a `Routing`
   * instance or a map of paths to routing targets.
   *
   * _Overwrite this in your implementation!_
   *
   * @return web.Handler|function(web.Request, web.Response): var|[:var]
   */
  public abstract function routes();

  /**
   * Installs global filters
   *
   * @param  web.Filter|function(web.Request, web.Response, web.filters.Invocation): var|web.Filter[] $arg
   * @return void
   */
  public function install($arg) {
    if ($arg instanceof Filter || $arg instanceof Closure) {
      $this->filters[]= $arg;
    } else {
      foreach ((array)$arg as $filter) {
        $this->filters[]= $filter;
      }
    }
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