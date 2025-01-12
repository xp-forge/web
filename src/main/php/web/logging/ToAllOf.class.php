<?php namespace web\logging;

/**
 * Log sink which logs to all given sinks
 *
 * @test  web.unittest.logging.ToAllOfTest
 */
class ToAllOf extends Sink {
  private $sinks= [];

  /**
   * Creates a sink writing to all given other sinks
   *
   * @param  (web.log.Sink|util.log.LogCategory|function(string, string, string, [:var]): void)... $arg
   */
  public function __construct(... $args) {
    foreach ($args as $arg) {
      if ($arg instanceof self) {
        $this->sinks= array_merge($this->sinks, $arg->sinks);
      } else if ($arg instanceof parent) {
        $this->sinks[]= $arg;
      } else {
        $this->sinks[]= parent::of($arg);
      }
    }
  }

  /** @return web.log.Sink[] */
  public function sinks() { return $this->sinks; }

  /** @return string */
  public function target() {
    $s= '';
    foreach ($this->sinks as $sink) {
      $s.= ' & '.$sink->target();
    }
    return '('.substr($s, 3).')';
  }

  /**
   * Writes a log entry
   *
   * @param  string $status
   * @param  string $method
   * @param  string $uri
   * @param  [:var] $hints Optional hints
   * @return void
   */
  public function log($status, $method, $uri, $hints) {
    foreach ($this->sinks as $sink) {
      $sink->log($status, $method, $uri, $hints);
    }
  }
}