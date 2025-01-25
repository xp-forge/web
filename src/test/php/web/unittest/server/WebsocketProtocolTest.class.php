<?php namespace web\unittest\server;

use test\{Assert, Before, Test, Values};
use util\{Bytes, Objects};
use web\Logging;
use web\unittest\Channel;
use websocket\Listener;
use xp\web\srv\WebsocketProtocol;

class WebsocketProtocolTest {
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

    $protocol= new WebsocketProtocol(newinstance(Listener::class, [], $listener), Logging::of($logging));
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
    new WebsocketProtocol(new class() extends Listener {
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
}