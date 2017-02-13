<?php namespace web;

use web\routing\Paths;
use web\filters\Invocation;

class Filters {

  /**
   * Creates a new instance
   *
   * @param  web.Filter[] $filters
   * @param  [:web.Routing]|web.Routing $routing
   */
  public function __construct($filters, $routing) {
    $this->filters= $filters;

    // ['/pattern' => target1, '/pattern2' => target2]
    if (is_array($routing)) {
      $this->target= new Paths($routing);
    } else {
      $this->target= $routing;
    }
  }

  public function route($request, $response) {
    return (new Invocation($this->target, $this->filters))->proceed($request, $response);
  }
}