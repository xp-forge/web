<?php namespace web;

use web\routing\Match;
use web\handler\Call;

class Route {
  private $match, $hander;

  /**
   * Creates a new route
   *
   * @param  web.routing.Match|function(web.Request): ([:string]|bool) $match
   * @param  web.Handler|function(web.Request, web.Response): var $handler
   */
  public function __construct($match, $handler) {
    if ($match instanceof Match) {
      $this->match= $match;
    } else {
      $this->match= newinstance(Match::class, [], ['matches' => $match]);
    }

    if ($handler instanceof Handler) {
      $this->handler= $handler;
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
    $matches= $this->match->matches($request);

    // Matches is one of:
    // - NULL or FALSE: Route doesn't match
    // - TRUE: Route matches
    // - A map: Route matches and provides pairs to pass to request
    if (null === $matches || false === $matches) return null;
    if (true === $matches) return $this->handler;

    foreach ((array)$matches as $key => $value) {
      $request->pass($key, $value);
    }
    return $this->handler;
  }
}