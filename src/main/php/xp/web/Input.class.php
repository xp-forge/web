<?php namespace xp\web;

class Input implements \web\io\Input {
  private $socket;
  private $buffer= '';

  /**
   * Creates a new input instance which reads from a socket
   *
   * @param  peer.Socket $socket
   */
  public function __construct($socket) {
    $this->socket= $socket;
    $this->buffer= '';
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

  /** @return iterable */
  public function headers() {
    while ($line= $this->readLine()) {
      sscanf($line, "%[^:]: %[^\r]", $name, $value);
      yield $name => $value;
    }
  }
}