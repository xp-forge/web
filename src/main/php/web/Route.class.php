<?php namespace web;

use web\handler\Call;
use web\routing\RouteMatch;

/** @deprecated */
class Route {
  private $match, $handler;

  /**
   * Creates a new route
   *
   * @param  web.routing.Match|function(web.Request): web.Handler $match
   * @param  web.Handler|function(web.Request, web.Response): var|[:var] $handler
   */
  public function __construct($match, $handler) {
    if ($match instanceof RouteMatch) {
      $this->match= $match;
    } else {
      $this->match= newinstance(RouteMatch::class, [], ['matches' => $match]);
    }

    if ($handler instanceof Handler) {
      $this->handler= $handler;
    } else if (is_array($handler)) {
      $this->handler= Routing::cast($handler);
    } else {
      $this->handler= new Call($handler);
    }
  }

  /**
   * Routes request and returns handler
   *
   * @param  web.Request $request
   * @return web.Handler
   */
  public function route($request) {
    if ($this->match->matches($request)) {
      return $this->handler;
    } else {
      return null;
    }
  }
}