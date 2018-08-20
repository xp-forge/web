<?php namespace web;

use web\log\Sink;

class Logging {
  private $sink;

  /**
   * Create an instance with a given sink
   *
   * @param  ?web.log.Sink $sink
   */
  public function __construct(Sink $sink= null) {
    $this->sink= $sink;
  }

  /** @return ?web.log.Sink */
  public function sink() { return $this->sink; }

  /**
   * Create an instance from a given command line argument
   *
   * @param  string $arg
   * @return self
   */
  public static function of($arg) {
    return new self(Sink::of($arg));
  }

  /**
   * Replace sink
   *
   * @param  string|?web.log.Sink $sink
   */
  public function replace($sink) {
    if (null === $sink || $sink instanceof Sink) {
      $this->sink= $sink;
    } else {
      $this->sink= Sink::of($sink);
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
  public function log($request, $response, $message= null) {
    $this->sink && $this->sink->log($request, $response, $message);
  }

  /**
   * Returns logging target
   *
   * @return string
   */
  public function target() {
    return $this->sink ? $this->sink->target() : '(no logging)';
  }
}