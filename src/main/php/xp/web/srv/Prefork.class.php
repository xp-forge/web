<?php namespace xp\web\srv;

use peer\server\PreforkingServer;

/**
 * Serves requests from a perforking server
 *
 * @ext   pcntl
 * @see   xp://peer.server.PreforkingServer
 */
class Prefork extends Standalone {

  /**
   * Creates a new instance
   *
   * @param  string $host
   * @param  int $port
   * @param  int $children How many children to initially fork, defaults to 10
   */
  public function __construct($host, $port, $children= 10) {
    parent::__construct(new PreforkingServer($host, $port, $children), 'http://'.$host.':'.$port.'/');
  }
}