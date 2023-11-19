<?php namespace web\logging;

class ToFunction extends Sink {
  private $function;

  /** @param callable $function */
  public function __construct($function) {
    $this->function= cast($function, 'function(web.Request, web.Response, [:var]): void');
  }

  /**
   * Writes a log entry
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  [:var] $hints Optional hints
   * @return void
   */
  public function log($request, $response, $hints) {
    $this->function->__invoke($request, $response, $hints);
  }
}