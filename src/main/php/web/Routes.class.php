<?php namespace web;

use web\handler\Call;
use web\routing\CannotRoute;

/**
 * Routing takes care of directing the request to the correct target
 * by using one or more routes given to it.
 *
 * @test  web.unittest.RoutesTest
 */
class Routes implements Handler {
  private $routes= [];
  private $top= false;
  private $default= null;

  /**
   * Casts given routes to an instance of `Routes`. The argument may be one of:
   *
   * - An instance of `Routes`, in which case it is returned directly
   * - A map of definitions => handlers, which are passed to `route()`
   * - A handler, which becomes the argument to `default()`.
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
        $r->route($definition, $target);
      }
    } else {
      $r= (new self())->default($routes);
    }

    $r->top= $top;
    return $r;
  }

  /** @return [:web.Handler] */
  public function routes() { return $this->routes; }

  /**
   * Routes a given match to the specified target.
   *
   * - `GET` matches GET requests
   * - `GET /` matches GET requests to any path
   * - `GET /test` matches GET requests inside /test
   * - `GET|POST` matches GET and POST requests
   * - `/` matches any request to any path
   * - `/test` matches any request inside /test
   *
   * @param  string $match
   * @param  web.Handler|function(web.Request, web.Response): var $target
   * @return self
   */
  public function route($match, $target) {
    static $quote= ['#' => '\\#', '.' => '\\.'];

    $handler= $target instanceof Handler ? $target : new Call($target);
    if ('/' === $match[0]) {
      $this->routes['#^[A-Z]+ '.strtr(rtrim($match, '/'), $quote).'/#']= $handler;
    } else {
      sscanf($match, "%[A-Z|] %[^\r]", $methods, $path);
      $this->routes['#^'.$methods.' '.(null === $path ? '' : strtr(rtrim($path, '/'), $quote)).'/#']= $handler;
    }
    return $this;
  }

  /**
   * Maps all requests not otherwise mapped to a given target.
   *
   * @param  web.Handler|function(web.Request, web.Response): var $target
   * @return self
   */
  public function default($target) {
    $this->default= $target instanceof Handler ? $target : new Call($target);
    return $this;
  }

  /**
   * Routes a request to the handler specified by this routing instance's
   * routes. Throws a `NotFound` error if not route is matched and no
   * default route exists.
   * 
   * @param  web.Request $request
   * @return web.Handler
   * @throws web.NotFound
   */
  public function target($request) {
    $match= $request->method().' '.rtrim($request->uri()->path(), '/').'/';
    foreach ($this->routes as $pattern => $handler) {
      if (preg_match($pattern, $match)) return $handler;
    }
    if ($this->default) return $this->default;

    throw new NotFound($request->uri()->path());
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

    dispatch: $result= $this->target($request)->handle($request, $response);
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