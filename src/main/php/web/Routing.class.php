<?php namespace web;

use web\routing\Target;
use web\routing\Path;
use web\routing\Matches;
use web\handler\Call;
use web\routing\CannotRoute;
use lang\Object;

/**
 * @test  xp://web.unittest.RoutingTest
 */
class Routing {
  private $fallback= null;
  private $routes= [];

  /**
   * Casts given routes to an instance of this Routing. Routes may be one of:
   *
   * - An instance of `Routing`, in which case it is returned directly
   * - A map of definitions => handlers, which are passed to `mapping()`
   * - A handler, which becomes the argument to `fallback()`.
   *
   * @param  var $routes
   * @return self
   */
  public static function cast($routes) {
    if ($routes instanceof self) {
      return $routes;
    } else if (is_array($routes)) {
      $routing= new self();
      foreach ($routes as $definition => $target) {
        $routing->matching($definition, $target);
      }
      return $routing;
    } else {
      return (new self())->fallbacks($routes);
    }
  }

  /** @return web.routing.Route[] */
  public function routes() { return $this->routes; }

  /**
   * Adds a given route and returns this routing instance
   *
   * @param  web.routing.Route $route
   * @return self
   */
  public function with(Route $route) {
    $this->routes[]= $route;
    return $this;
  }

  /**
   * Matches a given definition, routing it to the specified target.
   *
   * - `GET` matches GET requests
   * - `GET /` matches GET requests to any path
   * - `GET /test` matches GET requests inside /test
   * - `GET|POST` matches GET and POST requests
   * - `/` matches any request to any path
   * - `/test` matches any request inside /test
   *
   * @param  string $definition
   * @param  web.Handler|function(web.Request, web.Response): void $target
   * @return self
   */
  public function matching($definition, $target) {
    if ('/' === $definition{0}) {
      return $this->with(new Route(new Path($definition), $target));
    } else {
      sscanf($definition, '%[A-Z|] %[^ ]', $method, $path);
      return $this->with(new Route(new Target(explode('|', $method), $path ?: '*'), $target));
    }
  }

  /**
   * Maps all requests matching a given matcher to a given target.
   *
   * Always calls this as last method when creating the routing instance;
   * or else other mappings will not be honored.
   *
   * @param  web.routing.Match|function(web.Request): bool $matcher
   * @param  web.Handler|function(web.Request, web.Response): void $target
   * @return self
   */
  public function mapping($matcher, $target) {
    $this->routes[]= new Route($matcher, $target);
    return $this;
  }

  /**
   * Maps all requests not otherwise mapped to a given target.
   *
   * @param  web.Handler|function(web.Request, web.Response): void $target
   * @return self
   */
  public function fallbacks($handler) {
    if ($handler instanceof Handler) {
      $this->fallback= $handler;
    } else {
      $this->fallback= new Call($handler);
    }
    return $this;
  }

  /**
   * Routes a request to the handler specified by this routing instance's
   * routes. Throws a `CannotRoute` error if not route is matched and no
   * fallback route exists.
   * 
   * @param  web.Request $request
   * @return web.Handler
   * @throws web.Error
   */
  public function route($request) {
    foreach ($this->routes as $route) {
      if ($handler= $route->route($request)) return $handler;
    }

    if ($this->fallback) {
      return $this->fallback;
    } else {
      throw new CannotRoute($request);
    }
  }

  public function service($request, $response) {
    $this->route($request)->handle($request, $response);
  }
}