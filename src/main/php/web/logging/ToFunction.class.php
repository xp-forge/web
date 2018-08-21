<?php namespace web\logging;

class ToFunction extends Sink {
  private $function;

  /** @param callable $function */
  public function __construct($function) {
    $this->function= cast($function, 'function(web.Request, web.Response, string): void');
  }

  /**
   * Writes a log entry
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  string $message Additional message
   * @return void
   */
  public function log($request, $response, $message) {
    $this->function->__invoke($request, $response, $message);
  }
}