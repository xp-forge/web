<?php namespace xp\web\srv;

use peer\server\Server;

/**
 * Serves requests from a single-threaded server
 *
 * @see   xp://peer.server.Server
 */
class Serve extends Standalone {

  /**
   * Creates a new instance
   *
   * @param  string $host
   * @param  int $port
   */
  public function __construct($host, $port) {
    parent::__construct(new Server($host, $port), 'http://'.$host.':'.$port.'/');
  }
}