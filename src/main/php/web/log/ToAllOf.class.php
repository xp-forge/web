<?php namespace web\log;

class ToAllOf extends Sink {
  private $sinks= [];

  /**
   * Creates a sink writing to all given other sinks
   *
   * @param  (web.log.Sink|util.log.LogCategory|function(web.Request, web.Response, string): void)... $arg
   */
  public function __construct(... $args) {
    foreach ($args as $arg) {
      if ($arg instanceof parent) {
        $this->sinks[]= $arg;
      } else {
        $this->sinks[]= parent::of($arg);
      }
    }
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
    foreach ($this->sinks as $sink) {
      $sink->log($request, $response, $message);
    }
  }
}