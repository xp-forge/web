<?php namespace web\unittest\server;

use test\{Assert, Test};
use util\Bytes;
use web\unittest\Channel;
use websocket\Listener;
use xp\web\srv\WebsocketProtocol;

class WebsocketProtocolTest {

  /**
   * Handles incoming payload with a given listener
   *
   * @param  string[] $chunks
   * @param  function(websocket.protocol.Connection, string|util.Bytes): var $listener
   * @return string[]
   */
  private function handle($chunks, $listener) {
    $socket= new Channel($chunks);

    $protocol= new WebsocketProtocol(newinstance(Listener::class, [], $listener));
    $protocol->handleSwitch($socket, ['path' => '/ws', 'headers' => []]);
    try {
      foreach ($protocol->handleData($socket) as $_) { }
    } finally {
      $protocol->handleDisconnect($socket);
    }

    return $socket->out;
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
  public function answer_text_message() {
    $out= $this->handle(["\x81\x04", "Test"], function($conn, $message) use(&$received) {
      $conn->send('Re: '.$message);
    });

    Assert::equals(["\x81\x08Re: Test"], $out);
  }
}