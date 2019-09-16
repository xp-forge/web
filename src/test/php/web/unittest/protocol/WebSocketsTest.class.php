<?php namespace web\unittest\protocol;

use lang\IllegalStateException;
use peer\SocketTimeoutException;
use unittest\TestCase;
use util\Bytes;
use web\Environment;
use web\Listeners;
use web\Logging;
use web\logging\Sink;
use web\protocol\WebSockets;
use web\unittest\Channel;

class WebSocketsTest extends TestCase {
  const HANDSHAKE = "GET /ws HTTP/1.1\r\nSec-WebSocket-Version: 13\r\nSec-WebSocket-Key: VW5pdHRlc\r\n\r\n";

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
        return ['/ws' => $listener];
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
    $c= new Channel([self::HANDSHAKE]);
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

    $c= new Channel([self::HANDSHAKE]);
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

  #[@test, @values([["\x81", 'Test'], ["\x82", new Bytes('Test')]])]
  public function handle_message($type, $expected) {
    $invoked= [];
    $p= $this->fixture(function($conn, $message) use(&$invoked) {
      $invoked[]= [$conn->uri()->path() => $message];
    });

    $c= new Channel([self::HANDSHAKE, $type."\x04", 'Test']);
    $p->handleConnect($c);
    $p->handleData($c);

    $this->assertEquals([['/ws' => $expected]], $invoked);
  }

  #[@test]
  public function text_message_with_malformed_utf8() {
    $p= $this->fixture(function($conn, $message) { });

    $c= new Channel([self::HANDSHAKE, "\x81\x04", "\xfcber"]);
    $p->handleConnect($c);
    $p->handleData($c);

    $this->assertEquals(new Bytes("\x88\x02\x03\xef"), new Bytes(array_pop($c->out)));
    $this->assertTrue($c->closed);
  }

  #[@test]
  public function incoming_ping_answered_with_pong() {
    $p= $this->fixture(function($conn, $message) { });

    $c= new Channel([self::HANDSHAKE, "\x89\x04", 'Test']);
    $p->handleConnect($c);
    $p->handleData($c);

    $this->assertEquals(new Bytes("\x8a\x04Test"), new Bytes(array_pop($c->out)));
  }

  #[@test]
  public function incoming_pong_ignored() {
    $p= $this->fixture(function($conn, $message) { });

    $c= new Channel([self::HANDSHAKE, "\x8a\x04", 'Test']);
    $p->handleConnect($c);
    $out= $c->out;
    $p->handleData($c);

    $this->assertEquals($out, $c->out);
  }

  #[@test]
  public function close_without_payload() {
    $p= $this->fixture(function($conn, $message) { });

    $c= new Channel([self::HANDSHAKE, "\x88\x00"]);
    $p->handleConnect($c);
    $p->handleData($c);

    $this->assertEquals(new Bytes("\x88\x02\x03\xe8"), new Bytes(array_pop($c->out)));
    $this->assertTrue($c->closed);
  }

  #[@test]
  public function close_with_code_and_message_echoed() {
    $p= $this->fixture(function($conn, $message) { });

    $c= new Channel([self::HANDSHAKE, "\x88\x06", "\x0b\xb8Test"]);
    $p->handleConnect($c);
    $p->handleData($c);

    $this->assertEquals(new Bytes("\x88\x06\x0b\xb8Test"), new Bytes(array_pop($c->out)));
    $this->assertTrue($c->closed);
  }

  #[@test]
  public function close_with_illegal_client_code() {
    $p= $this->fixture(function($conn, $message) { });

    $c= new Channel([self::HANDSHAKE, "\x88\x06", "\x03\xecTest"]);
    $p->handleConnect($c);
    $p->handleData($c);

    $this->assertEquals(new Bytes("\x88\x02\x03\xea"), new Bytes(array_pop($c->out)));
    $this->assertTrue($c->closed);
  }

  #[@test]
  public function close_with_malformed_utf8() {
    $p= $this->fixture(function($conn, $message) { });

    $c= new Channel([self::HANDSHAKE, "\x88\x06", "\x03\xec\xfcber"]);
    $p->handleConnect($c);
    $p->handleData($c);

    $this->assertEquals(new Bytes("\x88\x02\x03\xef"), new Bytes(array_pop($c->out)));
    $this->assertTrue($c->closed);
  }

  #[@test]
  public function exceptions_are_logged() {
    $logged= [];
    $this->log= new Logging(newinstance(Sink::class, [], [
      'log' => function($kind, $uri, $status, $error= null) use(&$logged) {
        $logged[]= [$kind, $uri->path(), $status, $error ? nameof($error).':'.$error->getMessage() : null];
      }
    ]));
    $p= $this->fixture(function($conn, $message) { throw new IllegalStateException('Test'); });

    $c= new Channel([self::HANDSHAKE, "\x81\x04", 'Test']);
    $p->handleConnect($c);
    $p->handleData($c);

    $this->assertEquals([['TEXT', '/ws', 'ERR', 'lang.IllegalStateException:Test']], $logged);
  }

  #[@test]
  public function native_exceptions_are_wrapped() {
    $logged= [];
    $this->log= new Logging(newinstance(Sink::class, [], [
      'log' => function($kind, $uri, $status, $error= null) use(&$logged) {
        $logged[]= [$kind, $uri->path(), $status, $error ? nameof($error).':'.$error->getMessage() : null];
      }
    ]));
    $p= $this->fixture(function($conn, $message) { throw new \Exception('Test'); });

    $c= new Channel([self::HANDSHAKE, "\x81\x04", 'Test']);
    $p->handleConnect($c);
    $p->handleData($c);

    $this->assertEquals([['TEXT', '/ws', 'ERR', 'lang.XPException:Test']], $logged);
  }
}