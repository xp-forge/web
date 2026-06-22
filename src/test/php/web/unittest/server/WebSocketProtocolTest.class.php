<?php namespace web\unittest\server;

use test\{Assert, Before, Test, Values};
use util\{Bytes, Objects};
use web\Logging;
use web\unittest\Channel;
use websocket\Listener;
use websocket\protocol\Connection;
use xp\web\srv\WebSocketProtocol;

class WebSocketProtocolTest {
  private $noop;

  /**
   * Handles incoming payload with a given listener
   *
   * @param  string[] $chunks
   * @param  function(websocket.protocol.Connection, string|util.Bytes): var $listener
   * @param  ?function(string, string, string, [:var]): var $logging
   * @return string[]
   */
  private function handle($chunks, $listener, $logging= null) {
    $socket= new Channel($chunks);

    $protocol= new WebSocketProtocol(newinstance(Listener::class, [], $listener), Logging::of($logging));
    $protocol->handleSwitch($socket, ['path' => '/ws', 'headers' => []]);
    try {
      foreach ($protocol->handleData($socket) as $_) { }
    } finally {
      $protocol->handleDisconnect($socket);
    }

    return $socket->out;
  }

  #[Before]
  public function noop() {
    $this->noop= function($conn, $message) {
      // NOOP
    };
  }

  #[Test]
  public function can_create() {
    new WebSocketProtocol(new class() extends Listener {
      public function message($conn, $message) { }
    });
  }

  #[Test]
  public function receive_text_message() {
    $received= [];
    $this->handle(["\x81\x04", "Test"], function($conn, $message) use(&$received) {
      $received[]= $message;
    });

    Assert::equals(['Test'], $received);
  }

  #[Test]
  public function receive_binary_message() {
    $received= [];
    $this->handle(["\x82\x02", "\x47\x11"], function($conn, $message) use(&$received) {
      $received[]= $message;
    });

    Assert::equals([new Bytes("\x47\x11")], $received);
  }

  #[Test]
  public function send_messages() {
    $out= $this->handle(["\x81\x04", "Test"], function($conn, $message) {
      $conn->send('Re: '.$message);
      $conn->send(new Bytes("\x47\x11"));
    });

    Assert::equals(["\x81\x08Re: Test", "\x82\x02\x47\x11"], $out);
  }

  #[Test]
  public function answers_ping_with_pong_automatically() {
    $out= $this->handle(["\x89\x04", "Test"], $this->noop);
    Assert::equals(["\x8a\x04Test"], $out);
  }

  #[Test]
  public function default_close() {
    $out= $this->handle(["\x88\x00"], $this->noop);
    Assert::equals(["\x88\x02\x03\xe8"], $out);
  }

  #[Test]
  public function answer_with_client_code_and_reason() {
    $out= $this->handle(["\x88\x06", "\x03\xe8Test"], $this->noop);
    Assert::equals(["\x88\x06\x03\xe8Test"], $out);
  }

  #[Test]
  public function protocol_error() {
    $out= $this->handle(["\x88\x02", "\x03\xf7"], $this->noop);
    Assert::equals(["\x88\x02\x03\xea"], $out);
  }

  #[Test, Values([[["\x81\x04", "Test"], 'TEXT /ws'], [["\x82\x02", "\x47\x11"], 'BINARY /ws']])]
  public function logs_messages($input, $expected) {
    $logged= [];
    $this->handle($input, $this->noop, function($status, $method, $uri, $hints) use(&$logged) {
      $logged[]= $method.' '.$uri.($hints ? ' '.Objects::stringOf($hints) : '');
    });
    Assert::equals([$expected], $logged);
  }

  #[Test]
  public function without_connection() {
    $fixture= new WebSocketProtocol($this->noop);

    Assert::null($fixture->connection('test-1'));
  }

  #[Test]
  public function add_connections() {
    $fixture= new WebSocketProtocol($this->noop);
    $a= $fixture->add(new Connection(null, 'test-a', '/'));
    $b= $fixture->add(new Connection(null, 'test-b', '/'));

    Assert::equals($a, $fixture->connection('test-a'));
    Assert::equals($b, $fixture->connection('test-b'));
  }

  #[Test, Values([[['test-a'], ['a' => ['Hello']]], [['test-b'], ['b' => ['Hello']]]])]
  public function transmit($targets, $expected) {
    $transmitted= [];
    $fixture= new WebSocketProtocol($this->noop);
    $a= $fixture->add(newinstance(Connection::class, [null, 'test-a', '/'], [
      'send' => function($payload) use(&$transmitted) { $transmitted['a'][]= $payload; }
    ]));
    $b= $fixture->add(newinstance(Connection::class, [null, 'test-b', '/'], [
      'send' => function($payload) use(&$transmitted) { $transmitted['b'][]= $payload; }
    ]));
    $fixture->transmit($targets, 'Hello');

    Assert::equals($expected, $transmitted);
  }

  #[Test]
  public function broadcast() {
    $transmitted= [];
    $fixture= new WebSocketProtocol($this->noop);
    $a= $fixture->add(newinstance(Connection::class, [null, 'test-a', '/'], [
      'send' => function($payload) use(&$transmitted) { $transmitted['a'][]= $payload; }
    ]));
    $b= $fixture->add(newinstance(Connection::class, [null, 'test-b', '/'], [
      'send' => function($payload) use(&$transmitted) { $transmitted['b'][]= $payload; }
    ]));
    $fixture->broadcast('Hello');

    Assert::equals(['a' => ['Hello'], 'b' => ['Hello']], $transmitted);
  }

  #[Test, Values([[['test-a'], ['a', 'b']], [['test-b'], ['b', 'a']]])]
  public function broadcast_prioritizing($targets, $order) {
    $transmitted= [];
    $fixture= new WebSocketProtocol($this->noop);
    $a= $fixture->add(newinstance(Connection::class, [null, 'test-a', '/'], [
      'send' => function($payload) use(&$transmitted) { $transmitted['a'][]= $payload; }
    ]));
    $b= $fixture->add(newinstance(Connection::class, [null, 'test-b', '/'], [
      'send' => function($payload) use(&$transmitted) { $transmitted['b'][]= $payload; }
    ]));
    $fixture->broadcast('Hello', $targets);

    Assert::equals($order, array_keys($transmitted));
  }
}