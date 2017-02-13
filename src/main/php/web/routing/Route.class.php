<?php namespace web\routing;

use web\Handling;

class Route {
  private $match, $hander;

  /**
   * Creates a new route
   *
   * @param  web.routing.Match|function(web.Request): web.Handler $match
   * @param  web.Handler|function(web.Request, web.Response): void $handler
   */
  public function __construct($match, $handler) {
    $this->match= Matches::for($match);
    $this->handler= Handling::for($handler);
  }

  /**
   * Routes request and returns handler
   *
   * @param  web.Request $request
   * @param  web.Handler
   */
  public function route($request) {
    if ($this->match->matches($request)) {
      return $this->handler;
    } else {
      return null;
    }
  }
}