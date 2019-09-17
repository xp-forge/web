<?php namespace web;

use web\filters\Invocation;

class Filters implements Handler {
  private $filters;

  /**
   * Creates a new instance
   *
   * @param  web.Filter[] $filters
   * @param  [:web.Routing]|web.Routing $routing
   */
  public function __construct($filters, $routing) {
    $this->filters= $filters;
    $this->routing= Routing::cast($routing);
  }

  /** @return web.Filter[] */
  public function all() { return $this->filters; }

  public function handle($request, $response) {
    return (new Invocation($this->routing, $this->filters))->proceed($request, $response);
  }
}