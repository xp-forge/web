<?php namespace web;

use web\handler\Call;

/**
 * Routing takes care of directing the request to the correct target
 * by using one or more routes given to it.
 *
 * @test  web.unittest.RoutingTest
 */
class Routing implements Handler {
  private $routes= [];
  private $top= false;
  private $fallback= null;

  /**
   * Casts given routes to an instance of this Routing. Routes may be one of:
   *
   * - An instance of `Routing`, in which case it is returned directly
   * - A map of definitions => handlers, which are passed to `matching()`
   * - A handler, which becomes the argument to `fallback()`.
   *
   * @param  web.Handler|web.Application|function(web.Request, web.Response): var|[:var] $routes
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

  /** @return [:web.Handler] */
  public function routes() { return $this->routes; }

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
    static $quote= ['#' => '\\#', '.' => '\\.'];

    $handler= $target instanceof Handler ? $target : new Call($target);
    foreach ((array)$definitions as $definition) {
      if ('/' === $definition[0]) {
        $this->routes['#^[A-Z]+ '.strtr(rtrim($definition, '/'), $quote).'/#']= $handler;
      } else {
        sscanf($definition, "%[A-Z|] %[^\r]", $methods, $path);
        $this->routes['#^'.$methods.' '.(null === $path ? '' : strtr(rtrim($path, '/'), $quote)).'/#']= $handler;
      }
    }
    return $this;
  }

  /**
   * Maps all requests not otherwise mapped to a given target.
   *
   * @param  web.Handler|function(web.Request, web.Response): var $target
   * @return self
   */
  public function fallbacks($target) {
    $this->fallback= $target instanceof Handler ? $target : new Call($target);
    return $this;
  }

  /**
   * Routes a request to the handler specified by this routing instance's
   * routes. Throws a `CannotRoute` error if not route is matched and no
   * fallback route exists.
   * 
   * @param  web.Request $request
   * @return web.Handler
   * @throws web.CannotRoute
   */
  public function route($request) {
    $match= $request->method().' '.rtrim($request->uri()->path(), '/').'/';
    foreach ($this->routes as $pattern => $handler) {
      if (preg_match($pattern, $match)) return $handler;
    }
    if ($this->fallback) return $this->fallback;

    throw new CannotRoute($request);
  }

  /**
   * Handle a request
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @return var
   */
  public function handle($request, $response) {
    $seen= [];

    dispatch: $result= $this->route($request)->handle($request, $response);
    if ($this->top && $result instanceof Dispatch) {
      $seen[$request->uri()->hashCode()]= true;
      $request->rewrite($result->uri());
      if (isset($seen[$request->uri()->hashCode()])) {
        throw new Error(508, 'Internal redirect loop caused by dispatch to '.$result->uri());
      }
      goto dispatch;
    }

    return $result;
  }
}