<?php namespace web\protocol;

use util\Bytes;

class Connection {
  const MAXLENGTH = 0x8000000;

  private $socket, $id, $uri, $headers;

  /**
   * Creates a new connection
   *
   * @param  peer.Socket $socket
   * @param  int $id
   * @param  util.URI $uri
   * @param  [:var] $headers
   */
  public function __construct($socket, $id, $uri, $headers= []) {
    $this->socket= $socket;
    $this->id= $id;
    $this->uri= $uri;
    $this->headers= $headers;
  }

  /** @return int */
  public function id() { return $this->id; }

  /** @return util.URI */
  public function uri() { return $this->uri; }

  /** @return [:var] */
  public function headers() { return $this->headers; }

  /**
   * Reads a certain number of bytes
   *
   * @param  int $length
   * @return string
   */
  private function read($length) {
    $r= '';
    do {
      $r.= $this->socket->readBinary($length - strlen($r));
    } while (strlen($r) < $length && !$this->socket->eof());
    return $r;
  }

  /**
   * Receive messages, handling fragmentation
   *
   * @return iterable
   */
  public function receive() {
    $packets= [
      Opcodes::TEXT    => '',
      Opcodes::BINARY  => '',
      Opcodes::CLOSE   => '',
      Opcodes::PING    => '',
      Opcodes::PONG    => '',
    ];

    $continue= [];
    do {
      $packet= $this->read(2);
      if (strlen($packet) < 2) return $this->socket->close();

      $final= $packet[0] & "\x80";
      $opcode= $packet[0] & "\x0f";
      $length= $packet[1] & "\x7f";
      $masked= $packet[1] & "\x80";

      if ("\x00" === $opcode) {
        $opcode= array_pop($continue);
      }

      // Verify opcode, send protocol error if unkown
      if (!isset($packets[$opcode])) {
        $this->transmit(Opcodes::CLOSE, pack('n', 1002));
        $this->socket->close();
        return;
      }

      if ("\x7e" === $length) {
        $read= unpack('n', $this->read(2))[1];
      } else if ("\x7f" === $length) {
        $read= unpack('J', $this->read(8))[1];
      } else {
        $read= ord($length);
      }

      // Verify length
      if ($read > self::MAXLENGTH) {
        $this->transmit(Opcodes::CLOSE, pack('n', 1003));
        $this->socket->close();
        return;
      }

      // Read data
      if ("\x00" === $masked) {
        $packets[$opcode].= $read > 0 ? $this->read($read) : '';
      } else {
        $mask= $this->read(4);
        $data= $read > 0 ? $this->read($read) : '';

        for ($i = 0; $i < strlen($data); $i+= 4) {
          $packets[$opcode].= $mask ^ substr($data, $i, 4);
        }
      }

      if ("\x00" === $final) {
        $continue[]= $opcode;
        continue;
      }

      yield $opcode => $packets[$opcode];
      $packets[$opcode]= '';
    } while ($continue);
  }


  /**
   * Transmits an answer
   *
   * @param  string $type One of the class constants TEXT | BINARY | CLOSE | PING | PONG
   * @param  string $payload
   * @return void
   */
  public function transmit($type, $payload) {
    $length= strlen($payload);
    if ($length < 126) {
      $this->socket->write(("\x80" | $type).chr($length).$payload);
    } else if ($length < 65536) {
      $this->socket->write(("\x80" | $type)."\x7e".pack('n', $length).$payload);
    } else {
      $this->socket->write(("\x80" | $type)."\x7f".pack('J', $length).$payload);
    }
  }

  /**
   * Sends an answer
   *
   * @param  util.Bytes|string $arg
   * @return void
   */
  public function send($arg) {
    if ($arg instanceof Bytes) {
      $this->transmit(Opcodes::BINARY, $arg);
    } else {
      $this->transmit(Opcodes::TEXT, $arg);
    }
  }
}