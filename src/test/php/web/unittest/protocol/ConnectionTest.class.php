<?php namespace web\unittest\protocol;

use unittest\TestCase;
use web\protocol\Connection;
use web\protocol\Opcodes;
use web\unittest\Channel;

class ConnectionTest extends TestCase {
  const ID = 0;

  /**
   * Receive all messages from a given input channel
   *
   * @param  web.unittest.Channel $channel
   * @return []
   */
  private function receive($channel) {
    $conn= new Connection($channel, self::ID, '/', []);
    $r= [];
    foreach ($conn->receive() as $type => $message) {
      $r[]= [$type => $message];
    }
    return $r;
  }

  #[@test]
  public function can_create() {
    new Connection(new Channel([]), self::ID, '/', []);
  }

  #[@test]
  public function id() {
    $this->assertEquals(self::ID, (new Connection(new Channel([]), self::ID, '/', []))->id());
  }

  #[@test, @values([['/'], ['/test']])]
  public function uri($value) {
    $this->assertEquals($value, (new Connection(new Channel([]), self::ID, $value, []))->uri());
  }

  #[@test, @values([[[]], [['User-Agent' => 'Test', 'Accept' => '*/*']]])]
  public function headers($value) {
    $this->assertEquals($value, (new Connection(new Channel([]), self::ID, '/test', $value))->headers());
  }

  #[@test]
  public function text() {
    $received= $this->receive(new Channel(["\x81\x04", "Test"]));
    $this->assertEquals(
      [[Opcodes::TEXT => 'Test']],
      $received
    );
  }

  #[@test]
  public function fragmented_text() {
    $received= $this->receive(new Channel(["\x01\x05", "Hello", "\x80\x06", " World"]));
    $this->assertEquals(
      [[Opcodes::TEXT => 'Hello World']],
      $received
    );
  }

  #[@test]
  public function fragmented_text_with_ping_inbetween() {
    $received= $this->receive(new Channel(["\x01\x05", "Hello", "\x89\x01", "!", "\x80\x06", " World"]));
    $this->assertEquals(
      [[Opcodes::PING => '!'], [Opcodes::TEXT => 'Hello World']],
      $received
    );
  }

  #[@test]
  public function closes_connection_on_invalid_opcode() {
    $channel= new Channel(["\x8F\x00"]);
    $this->receive($channel);

    // 0x80 | 0x08 (CLOSE), 2 bytes, pack("n", 1002)
    $this->assertEquals(["\x88\x02\x03\xea"], $channel->out);
    $this->assertTrue($channel->closed, 'Channel closed');
  }
}