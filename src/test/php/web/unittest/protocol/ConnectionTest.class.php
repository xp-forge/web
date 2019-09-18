<?php namespace web\unittest\protocol;

use unittest\TestCase;
use util\Bytes;
use util\URI;
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
    $conn= new Connection($channel, self::ID, new URI('/'), []);
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
  public function masked_text() {
    $received= $this->receive(new Channel(["\x81\x86", "\x01\x02\x03\x04", "Ugppdf"]));
    $this->assertEquals(
      [[Opcodes::TEXT => 'Tested']],
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

  #[@test, @values(['', "\x81"])]
  public function closes_connection_on_invalid_packet($bytes) {
    $channel= new Channel([$bytes]);
    $this->receive($channel);

    $this->assertEquals([], $channel->out);
    $this->assertTrue($channel->closed, 'Channel closed');
  }

  #[@test]
  public function closes_connection_on_invalid_opcode() {
    $channel= new Channel(["\x8f\x00"]);
    $this->receive($channel);

    // 0x80 | 0x08 (CLOSE), 2 bytes, pack("n", 1002)
    $this->assertEquals(["\x88\x02\x03\xea"], $channel->out);
    $this->assertTrue($channel->closed, 'Channel closed');
  }

  #[@test]
  public function closes_connection_when_exceeding_max_length() {
    $channel= new Channel(["\x81\x7f", pack('J', Connection::MAXLENGTH + 1)]);
    $this->receive($channel);

    // 0x80 | 0x08 (CLOSE), 2 bytes, pack("n", 1003)
    $this->assertEquals(["\x88\x02\x03\xeb"], $channel->out);
    $this->assertTrue($channel->closed, 'Channel closed');
  }

  #[@test]
  public function send_string() {
    $channel= new Channel([]);
    (new Connection($channel, self::ID, new URI('/'), []))->send('Test');

    $this->assertEquals(["\x81\x04Test"], $channel->out);
  }

  #[@test]
  public function send_bytes() {
    $channel= new Channel([]);
    (new Connection($channel, self::ID, new URI('/'), []))->send(new Bytes('Test'));

    $this->assertEquals(["\x82\x04Test"], $channel->out);
  }

  #[@test, @values([
  #  [0, "\x81\x00"],
  #  [1, "\x81\x01"],
  #  [125, "\x81\x7d"],
  #  [126, "\x81\x7e\x00\x7e"],
  #  [65535, "\x81\x7e\xff\xff"],
  #  [65536, "\x81\x7f\x00\x00\x00\x00\x00\x01\x00\x00"],
  #])]
  public function send($length, $header) {
    $string= str_repeat('*', $length);
    $channel= new Channel([]);
    (new Connection($channel, self::ID, new URI('/'), []))->send($string);

    $this->assertEquals(new Bytes($header), new Bytes(substr($channel->out[0], 0, strlen($header))));
    $this->assertEquals(strlen($header) + $length, strlen($channel->out[0]));
  }

  #[@test, @values([
  #  [0, ["\x81\x00"]],
  #  [1, ["\x81\x01"]],
  #  [125, ["\x81\x7d"]],
  #  [126, ["\x81\x7e", "\x00\x7e"]],
  #  [65535, ["\x81\x7e", "\xff\xff"]],
  #  [65536, ["\x81\x7f", "\x00\x00\x00\x00\x00\x01\x00\x00"]],
  #])]
  public function read($length, $header) {
    $string= str_repeat('*', $length);
    $channel= new Channel(array_merge($header, [$string]));
    $message= (new Connection($channel, self::ID, new URI('/'), []))->receive()->current();

    $this->assertEquals($length, strlen($message));
  }
}