<?php namespace web\unittest;

use lang\Throwable;
use peer\ServerSocket;
use peer\server\AsynchronousServer;
use util\cmd\Console;
use web\{Environment, Logging};
use xp\web\srv\{Kernel, HttpProtocol, WebSocketProtocol};

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
    $log= new Logging(null);
    $kernel= new Kernel(new AsynchronousServer());
    $application= new TestingApplication(new Environment('test', '.', '.', '.', [], null));
    $application->initialize($kernel
      ->serving('http', new HttpProtocol($application, $log))
      ->serving('websocket', new WebSocketProtocol(null, $log))
    );

    $socket= new ServerSocket('127.0.0.1', $args[0] ?? 0);
    try {
      $kernel->server->listen($socket, $kernel);
      Console::writeLinef('+ Service %s:%d', $socket->host, $socket->port);

      $kernel->server->service();
    } catch (Throwable $e) {
      Console::writeLine('- ', $e->getMessage());
    }
  }
}