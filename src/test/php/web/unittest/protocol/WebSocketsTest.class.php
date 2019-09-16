<?php namespace web\unittest\protocol;

use peer\SocketTimeoutException;
use unittest\TestCase;
use web\Environment;
use web\Listeners;
use web\Logging;
use web\protocol\WebSockets;
use web\unittest\Channel;

class WebSocketsTest extends TestCase {
  private $log;

  /** @return void */
  public function setUp() {
    $this->log= new Logging(null);
  }

  /**
   * Creates a fixture
   *
   * @param  function(web.protocol.Connection, string): var $listener
   * @return web.protocol.Http
   */
  private function fixture($listener) {
    $listeners= newinstance(Listeners::class, [new Environment('test')], [
      'on' => function() use($listener) {
        return ['/' => $listener];
      }
    ]);

    $p= new WebSockets($listeners, $this->log);
    $p->initialize();
    return $p;
  }

  /**
   * Assertion helper
   *
   * @param  string $expected Regular expression without delimiters
   * @param  string[] $out
   * @throws unittest.AssertionFailedError
   */
  private function assertHttp($expected, $out) {
    $actual= implode('', $out);
    if (!preg_match('#^'.$expected.'$#m', $actual)) {
      $this->fail('=~', $actual, $expected);
    }
  }

  #[@test]
  public function can_create() {
    $this->fixture(function($conn, $message) { });
  }

  #[@test]
  public function initialize() {
    $p= $this->fixture(function($conn, $message) { });
    $p->initialize();
  }

  #[@test]
  public function handle_disconnect_closes_socket() {
    $p= $this->fixture(function($conn, $message) { });

    $c= new Channel([]);
    $p->handleDisconnect($c);
    $this->assertTrue($c->closed);
  }

  #[@test]
  public function handle_error_closes_socket() {
    $p= $this->fixture(function($conn, $message) { });

    $c= new Channel([]);
    $p->handleError($c, new SocketTimeoutException('Test', 42.0));
    $this->assertTrue($c->closed);
  }

  #[@test]
  public function handle_connect_reads_handshake() {
    $p= $this->fixture(function($conn, $message) { });

    $c= new Channel([
      "GET /ws HTTP/1.1\r\n".
      "Connection: Upgrade\r\n".
      "Upgrade: websocket\r\n".
      "Sec-WebSocket-Version: 13\r\n".
      "Sec-WebSocket-Key: VW5pdHRlc\r\n".
      "\r\n"
    ]);
    $p->handleConnect($c);

    $this->assertHttp(
      "HTTP/1.1 101 Switching Protocols\r\n".
      "Date: [A-Za-z]+, [0-9]+ [A-Za-z]+ [0-9]+ [0-9]+:[0-9]+:[0-9]+ GMT\r\n".
      "Connection: Upgrade\r\n".
      "Upgrade: websocket\r\n".
      "Sec-WebSocket-Accept: burhE5E1BXOFMByjTtUeclRFR9w=\r\n".
      "Content-Length: 0\r\n".
      "\r\n",
      $c->out
    );
  }

  #[@test]
  public function handle_connect_sets_timeout() {
    $p= $this->fixture(function($conn, $message) { });

    $c= new Channel([
      "GET /ws HTTP/1.1\r\n".
      "Connection: Upgrade\r\n".
      "Upgrade: websocket\r\n".
      "Sec-WebSocket-Version: 13\r\n".
      "Sec-WebSocket-Key: VW5pdHRlc\r\n".
      "\r\n"
    ]);
    $p->handleConnect($c);

    $this->assertEquals(600.0, $c->timeout);
  }

  #[@test]
  public function unsupported_ws_version() {
    $p= $this->fixture(function($conn, $message) { });

    $c= new Channel([
      "GET /ws HTTP/1.1\r\n".
      "Connection: Upgrade\r\n".
      "Upgrade: websocket\r\n".
      "Sec-WebSocket-Version: 99\r\n".
      "\r\n"
    ]);
    $p->handleConnect($c);

    $this->assertHttp(
      "HTTP/1.1 400 Bad Request\r\n".
      "Date: [A-Za-z]+, [0-9]+ [A-Za-z]+ [0-9]+ [0-9]+:[0-9]+:[0-9]+ GMT\r\n".
      "Connection: close\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 32\r\n".
      "\r\n".
      "Unsupported websocket version 99",
      $c->out
    );
  }
}