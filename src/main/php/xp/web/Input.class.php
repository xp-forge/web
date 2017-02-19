<?php namespace xp\web;

class Input implements \web\io\Input {
  private $socket;

  /**
   * Creates a new input instance which reads from a socket
   *
   * @param  peer.Socket $socket
   */
  public function __construct($socket) {
    $this->socket= $socket;
  }

  /** @return iterable */
  public function headers() {
    while ($line= $this->socket->readLine()) {
      sscanf($line, '%[^:]: %s', $name, $value);
      yield $name => $value;
    }
  }
}