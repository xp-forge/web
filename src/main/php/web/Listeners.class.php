<?php namespace web;

use lang\ElementNotFoundException;
use lang\IllegalArgumentException;
use web\protocol\WebSockets;

/**
 * At the heart of every WebSocket application stands this class.
 *
 * @test  xp://web.unittest.ListenersTest
 */
abstract class Listeners extends Service {
  private $dispatch= null;
  private $connections= [];

  /**
   * Cast listeners
   *
   * @param  web.Listener|function(web.protocol.Connection, string): var $arg
   * @return callable
   * @throws lang.IllegalArgumentException
   */
  public static function cast($arg) {
    if ($arg instanceof Listener) {
      return [$arg, 'message'];
    } else if (is_callable($arg)) {
      return $arg;
    } else {
      throw new IllegalArgumentException('Expected either a callable or a web.Listener instance, have '.typeof($arg));
    }
  }

  /**
   * Attach a connection
   *
   * @param  int $id
   * @param  web.protocol.Connection $connection
   * @return void
   */
  public function attach($id, $connection) {
    $this->connections[$id]= $connection;
  }

  /**
   * Get all previously attached connection
   *
   * @return [:web.protocol.Connection]
   */
  public function connections() { return $this->connections; }

  /**
   * Get a previously attached connection
   *
   * @param  int $id
   * @return web.protocol.Connection
   * @throws lang.ElementNotFoundException
   */
  public function connection($id) {
    if (isset($this->connections[$id])) return $this->connections[$id];

    throw new ElementNotFoundException('No such connection #'.$id);
  }

  /**
   * Remove a connection
   *
   * @param  int $id
   * @return void
   */
  public function detach($id) {
    unset($this->connections[$id]);
  }

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
          $this->dispatch['#.#']= self::cast($handler);
        } else {
          $this->dispatch['#^'.$path.'(/?|/.+)$#']= self::cast($handler);
        }
      }
    }

    $path= $connection->uri()->path();
    foreach ($this->dispatch as $pattern => $handler) {
      if (preg_match($pattern, $path)) return $handler($connection, $message);
    }
    return null;
  }

  /**
   * Listeners can be accessed via WebSockets protocol on a given server instance
   *
   * @param  peer.server.Server $server
   * @return web.protocol.Protocol
   */
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