<?php namespace web\filters;

use Traversable;
use web\{Routes, Dispatch};

/**
 * Filter chain invocation
 *
 * @test  web.unittest.filters.InvocationTest
 */
class Invocation {
  private $routing, $filters, $offset, $length;

  /**
   * Create a new Invocation
   *
   * @param  web.Handler|function(web.Request, web.Response): var|[:var] $routing
   * @param  web.Filter[] $filters
   */
  public function __construct($routing, $filters= []) {
    $this->routing= Routes::cast($routing);
    $this->filters= $filters;
    $this->offset= 0;
    $this->length= sizeof($filters);
  }

  /**
   * Proceed with the invocation
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @return iterable
   */
  public function proceed($request, $response) {
    if ($this->offset < $this->length) {
      return $this->filters[$this->offset++]->filter($request, $response, $this);
    }

    // Ensure the results of service invocation are iterable
    $return= $this->routing->handle($request, $response);
    return $return instanceof Traversable ? $return : (array)$return;
  }
}