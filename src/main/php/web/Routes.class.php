<?php namespace web;

use web\handler\Call;

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
   * @return self
   */
  public static function cast($routes) {
    if ($routes instanceof self) {
      return $routes;
    } else if ($routes instanceof Application) {
      return $routes->routing();
    } else if (is_array($routes)) {
      $r= new self();
      foreach ($routes as $definition => $target) {
        $r->route($definition, $target);
      }
      return $r;
    } else {
      return (new self())->default($routes);
    }
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
   * - `/{id}` matches any path segment and passes it as request value "id"
   * - `/{id:[0-9]+}` matches `[0-9]+` and passes it as request value "id"
   *
   * @param  string $match
   * @param  web.Handler|function(web.Request, web.Response): var|[:var] $target
   * @param  string $base
   * @return self
   */
  public function route($match, $target, $base= '') {
    if (is_array($target)) {
      $base.= rtrim($match, '/');
      foreach ($target as $suffix => $nested) {
        $this->route($suffix, $nested, $base);
      }
    } else {
      if ('/' === $match[0]) {
        $methods= '[A-Z]+';
        $base.= rtrim($match, '/');
      } else {
        sscanf($match, "%[A-Z|] %[^\r]", $methods, $path);
        null === $path || $base.= rtrim($path, '/');
      }

      $pattern= preg_replace(
        ['/\{([^:}]+)?:([^}]+)\}/', '/\{([^}]+)\}/', '/[.#]/'],
        ['(?<$1>$2)', '(?<$1>[^/]+)', '\\\\$0'],
        $base
      );
      $this->routes["#^{$methods} {$pattern}/#"]= $target instanceof Handler
        ? $target
        : new Call($target)
      ;
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
      if (preg_match($pattern, $match, $matches)) {
        foreach ($matches as $key => $value) {
          is_string($key) && $request->pass($key, $value);
        }
        return $handler;
      }
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