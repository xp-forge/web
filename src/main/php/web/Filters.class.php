<?php namespace web;

use web\filters\Invocation;

/**
 * Filters acts as a handler and invokes all filters before passing control
 * on to the given handler.
 *
 * @see   web.Filter
 * @test  web.unittest.FiltersTest
 */
class Filters implements Handler {
  private $routing;
  private $filters= [];

  /**
   * Creates a new instance
   *
   * @param  (web.Filter|function(web.Request, web.Response, web.filters.Invocation)[] $filters
   * @param  web.Handler|function(web.Request, web.Response): var|[:var] $routing
   */
  public function __construct($filters, $routing) {
    foreach ($filters as $filter) {
      if ($filter instanceof Filter) {
        $this->filters[]= $filter;
      } else {
        $this->filters[]= newinstance(Filter::class, [], ['filter' => $filter]);
      }
    }
    $this->routing= Routes::cast($routing);
  }

  /**
   * Filter request
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @param  var
   */
  public function handle($request, $response) {
    return (new Invocation($this->routing, $this->filters))->proceed($request, $response);
  }
}