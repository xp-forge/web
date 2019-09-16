<?php namespace web\protocol;

/**
 * WebSocket opcodes enumeration
 *
 * @see   https://tools.ietf.org/html/rfc6455
 * @test  xp://web.unittest.protocol.OpcodesTest
 */
class Opcodes {
  const TEXT    = "\x01";
  const BINARY  = "\x02";
  const CLOSE   = "\x08";
  const PING    = "\x09";
  const PONG    = "\x0a";

  /**
   * Returns an opcode name for a given opcode
   *
   * @param  string $opcode
   * @return string
   */
  public static function nameOf($opcode) {
    static $opcodes= [
      self::TEXT    => 'TEXT',
      self::BINARY  => 'BINARY',
      self::CLOSE   => 'CLOSE',
      self::PING    => 'PING',
      self::PONG    => 'PONG',
    ];

    return isset($opcodes[$opcode]) ? $opcodes[$opcode] : sprintf('UNKNOWN(0x%02x)', ord($opcode));
  }
}