<?php namespace web;

use web\protocol\WebSockets;

/**
 * At the heart of every WebSocket application stands this class.
 *
 * @test  xp://web.unittest.ListenersTest
 */
abstract class Listeners extends Service {
  private $dispatch= null;

  /**
   * Dispatch a message
   *
   * @param  web.protocol.Connection $connection
   * @param  var $message
   * @return var
   */
  public function dispatch($connection, $message) {
    if (null === $this->dispatch) {
      $this->dispatch= [];
      foreach ($this->on() as $path => $handler) {
        if ('/' === $path) {
          $this->dispatch['#.#']= $handler;
        } else {
          $this->dispatch['#^'.$path.'(/?|/.+)$#']= $handler;
        }
      }
    }

    $path= $connection->uri()->path();
    foreach ($this->dispatch as $pattern => $handler) {
      if (preg_match($pattern, $path)) return $handler($connection, $message);
    }
    return null;
  }

  public function serve($server) {
    return new WebSockets($this, $this->environment->logging());
  }

  /**
   * Returns dispatching information
   *
   * @return [:callable]
   */
  public abstract function on();
}