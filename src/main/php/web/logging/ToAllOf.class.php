<?php namespace web\logging;

class ToAllOf extends Sink {
  private $sinks= [];

  /**
   * Creates a sink writing to all given other sinks
   *
   * @param  (web.log.Sink|util.log.LogCategory|function(web.Request, web.Response, string): void)... $arg
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
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  ?web.Error $error Optional error
   * @return void
   */
  public function log($request, $response, $error) {
    foreach ($this->sinks as $sink) {
      $sink->log($request, $response, $error);
    }
  }
}