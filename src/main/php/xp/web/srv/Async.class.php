<?php namespace xp\web\srv;

use peer\server\AsyncServer;

/**
 * Serves requests from a single-threaded asynchronous server
 *
 * @see   xp://peer.server.AsyncServer
 */
class Async extends Standalone {

  /**
   * Creates a new instance
   *
   * @param  string $host
   * @param  int $port
   */
  public function __construct($host, $port) {
    parent::__construct(new AsyncServer(), $host, $port);
  }
}