<?php namespace web;

use web\logging\Sink;
use web\logging\ToAllOf;

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
   * Pipe to a given sink
   *
   * @param  var $sink
   * @return self
   */
  public function pipe($sink) {
    if (null === $sink || $sink instanceof Sink) {
      $this->sink= $sink;
    } else {
      $this->sink= Sink::of($sink);
    }
    return $this;
  }

  /**
   * Tee to a given sink
   *
   * @param  var $sink
   * @return self
   */
  public function tee($sink) {
    if (null === $this->sink) {
      $this->pipe($sink);
    } else {
      $this->sink= new ToAllOf($this->sink, $sink);
    }
    return $this;
  }

  /**
   * Writes a log entry
   *
   * @param  string $kind
   * @param  util.URI $uri
   * @param  string $status
   * @param  ?web.Error $error Optional error
   * @return void
   */
  public function log($kind, $uri, $status, $error= null) {
    $this->sink && $this->sink->log($kind, $uri, $status, $error);
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