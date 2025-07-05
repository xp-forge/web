<?php namespace web\logging;

class ToFunction extends Sink {
  private $function;

  /** @param callable $function */
  public function __construct($function) {
    $this->function= cast($function, 'function(string, string, string, [:var]): void');
  }

  /**
   * Writes a log entry
   *
   * @param  string $status
   * @param  string $method
   * @param  string $resource
   * @param  [:var] $hints Optional hints
   * @return void
   */
  public function log($status, $method, $resource, $hints) {
    $this->function->__invoke($status, $method, $resource, $hints);
  }
}