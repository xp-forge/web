<?php namespace xp\web;

use peer\CryptoSocket;

class Input implements \web\io\Input {
  private $socket;
  private $method, $uri;
  private $buffer= '';

  /**
   * Creates a new input instance which reads from a socket
   *
   * @param  peer.Socket $socket
   */
  public function __construct($socket) {
    $this->socket= $socket;
    $this->buffer= '';

    if (null === ($message= $this->readLine())) return;
    sscanf($message, '%s %s HTTP/%d.%d', $this->method, $this->uri, $major, $minor);
  }

  /** @return string */
  public function readLine() {
    if (null === $this->buffer) return null;    // EOF

    while (false === ($p= strpos($this->buffer, "\r\n"))) {
      $chunk= $this->socket->readBinary();
      if ('' === $chunk) {
        $return= $this->buffer;
        $this->buffer= null;
        return $return;
      }
      $this->buffer.= $chunk;
    }

    $return= substr($this->buffer, 0, $p);
    $this->buffer= substr($this->buffer, $p + 2);
    return $return;
  }

  /** @return string */
  public function scheme() { return $this->socket instanceof CryptoSocket ? 'https' : 'http'; }

  /** @return string */
  public function method() { return $this->method; }

  /** @return sring */
  public function uri() { return $this->uri; }

  /** @return iterable */
  public function headers() {
    while ($line= $this->readLine()) {
      sscanf($line, "%[^:]: %[^\r]", $name, $value);
      yield $name => $value;
    }
  }

  /**
   * Reads a given number of bytes
   *
   * @param  int $length Pass -1 to read all
   * @return string
   */
  public function read($length= -1) {
    $data= $this->buffer;
    if (-1 === $length) {
      while (!$this->socket->eof()) {
        $data.= $this->socket->readBinary();
      }
      $this->buffer= null;
    } else {
      $eof= false;
      while (strlen($data) < $length) {
        $data.= $this->socket->readBinary($length - strlen($data));
        if ($eof= $this->socket->eof()) break;
      }
      $this->buffer= $eof ? null : '';
    }
    return $data;
  }
}