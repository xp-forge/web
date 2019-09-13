<?php namespace web\unittest;

use unittest\TestCase;
use web\protocol\Opcodes;

class OpcodesTest extends TestCase {

  #[@test, @values([
  #  [Opcodes::TEXT, 'TEXT'],
  #  [Opcodes::BINARY, 'BINARY'],
  #  [Opcodes::CLOSE, 'CLOSE'],
  #  [Opcodes::PING, 'PING'],
  #  [Opcodes::PONG, 'PONG'],
  #])]
  public function name($opcode, $name) {
    $this->assertEquals($name, Opcodes::nameOf($opcode));
  }

  #[@test]
  public function unknown_name() {
    $this->assertEquals('UNKNOWN(0xff)', Opcodes::nameOf("\xff"));
  }
}