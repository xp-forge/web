<?php namespace web;

use web\handler\Call;
use web\routing\{CannotRoute, Matches, Path, Target};

/**
 * Routing takes care of directing the request to the correct target
 * by using one or more routes given to it.
 *
 * @test  xp://web.unittest.RoutingTest
 */
class Routing {
  private $top= false;
  private $fallback= null;
  private $routes= [];

  /**
   * Casts given routes to an instance of this Routing. Routes may be one of:
   *
   * - An instance of `Routing`, in which case it is returned directly
   * - A map of definitions => handlers, which are passed to `matching()`
   * - A handler, which becomes the argument to `fallback()`.
   *
   * @param  self|[:var]|web.Handler|web.Application|function(web.Request, web.Response): var $routes
   * @param  bool $top Whether this is the top-level routing
   * @return self
   */
  public static function cast($routes, $top= false) {
    if ($routes instanceof self) {
      $r= $routes;
    } else if ($routes instanceof Application) {
      $r= $routes->routing();
    } else if (is_array($routes)) {
      $r= new self();
      foreach ($routes as $definition => $target) {
        $r->matching($definition, $target);
      }
    } else {
      $r= (new self())->fallbacks($routes);
    }

    $r->top= $top;
    return $r;
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
   * @param  string|string[] $definitions
   * @param  web.Handler|function(web.Request, web.Response): var $target
   * @return self
   */
  public function matching($definitions, $target) {
    foreach ((array)$definitions as $definition) {
      if ('/' === $definition[0]) {
        $matcher= new Path($definition);
      } else {
        sscanf($definition, '%[A-Z|] %[^ ]', $method, $path);
        $matcher= new Target(explode('|', $method), $path ?: '*');
      }
      $this->routes[]= new Route($matcher, $target);
    }
    return $this;
  }

  /**
   * Maps all requests matching a given matcher to a given target.
   *
   * Always calls this as last method when creating the routing instance;
   * or else other mappings will not be honored.
   *
   * @param  web.routing.Match|function(web.Request): bool $matcher
   * @param  web.Handler|function(web.Request, web.Response): var $target
   * @return self
   */
  public function mapping($matcher, $target) {
    $this->routes[]= new Route($matcher, $target);
    return $this;
  }

  /**
   * Maps all requests not otherwise mapped to a given target.
   *
   * @param  web.Handler|function(web.Request, web.Response): var $target
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

  /**
   * Service a request
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @return var
   */
  public function service($request, $response) {
    $seen= [];
    do {
      $result= $this->route($request)->handle($request, $response);
      if ($this->top && $result instanceof Dispatch) {
        $seen[$request->uri()->hashCode()]= true;
        $request->rewrite($result->uri());
        if (isset($seen[$request->uri()->hashCode()])) {
          throw new Error(508, 'Internal redirect loop caused by dispatch to '.$result->uri());
        }
        continue;
      }
      return $result;
    } while (true);
  }
}