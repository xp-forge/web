<?php namespace web\unittest;

use lang\Throwable;
use peer\ServerSocket;
use peer\server\AsyncServer;
use util\cmd\Console;
use web\{Environment, Logging};
use xp\web\srv\{Protocol, HttpProtocol, WebsocketProtocol};

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
    $socket= new ServerSocket('127.0.0.1', $args[0] ?? 0);
    $log= new Logging(null);

    $s= new AsyncServer();
    try {
      $s->listen($socket, Protocol::multiplex()
        ->serving('http', new HttpProtocol($application, $log))
        ->serving('websocket', new WebsocketProtocol(null, $log))
      );
      $s->init();
      Console::writeLinef('+ Service %s:%d', $socket->host, $socket->port);
      $s->service();
      $s->shutdown();
    } catch (Throwable $e) {
      Console::writeLine('- ', $e->getMessage());
    }
  }
}