<?php namespace web\unittest;

use lang\Throwable;
use peer\ServerSocket;
use peer\server\Server;
use util\cmd\Console;
use web\{Environment, Logging};
use xp\web\srv\HttpProtocol;

/**
 * Socket server used by integration tests. 
 *
 * Process interaction is performed by messages this server prints to
 * standard out:
 *
 * - Server listens on a free port @ 127.0.0.1
 * - On startup success, "+ Service (IP):(PORT)" is written
 * - On errors, "- " and the exception message are written
 */
class TestingServer {

  /** Starts the server */
  public static function main(array $args) {
    $application= new TestingApplication(new Environment('test', '.', '.', '.', [], null));

    $s= new Server();
    try {
      $s->listen(new ServerSocket('127.0.0.1', 0), new HttpProtocol($application, new Logging(null)));
      $s->init();
      Console::writeLinef('+ Service %s:%d', $s->socket->host, $s->socket->port);
      $s->service();
      $s->shutdown();
    } catch (Throwable $e) {
      Console::writeLine('- ', $e->getMessage());
    }
  }
}