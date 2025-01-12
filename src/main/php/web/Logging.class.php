<?php namespace web;

use web\logging\{Sink, ToAllOf};

class Logging {
  private $sink;

  /**
   * Create an instance with a given sink
   *
   * @param  ?web.log.Sink $sink
   */
  public function __construct($sink= null) {
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
   * Writes a HTTP exchange to the log
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  [:var] $hints Optional hints
   * @return void
   */
  public function exchange($request, $response, $hints= []) {
    if (!$this->sink) return;

    $uri= $request->uri()->path();
    if ($query= $request->uri()->query()) {
      $uri.= '?'.$query;
    }
    $this->sink->log($response->status(), $request->method(), $uri, $response->trace + $hints);
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
  public function log($status, $method, $uri, $hints= []) {
    $this->sink && $this->sink->log($status, $method, $uri, $hints);
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