<?php namespace web\filters;

use web\Routing;

/**
 * Filter chain invocation
 *
 * @test  xp://web.unittest.filters.InvocationTest
 */
class Invocation {
  private $routing, $filters, $offset, $length;

  /**
   * Create a new Invocation
   *
   * @param  web.Routing|[:var]|web.Handler|function(web.Request, web.Response): var $routes
   * @param  web.Filter[] $filters
   */
  public function __construct($routing, $filters= []) {
    $this->routing= Routing::cast($routing);
    $this->filters= $filters;
    $this->offset= 0;
    $this->length= sizeof($filters);
  }

  /**
   * Proceed with the invocation
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @return var
   */
  public function proceed($request, $response) {
    if ($this->offset < $this->length) {
      return $this->filters[$this->offset++]->filter($request, $response, $this);
    } else {
      return $this->routing->service($request, $response);
    }
  }
}