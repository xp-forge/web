<?php namespace xp\web\srv;

use web\io\{Buffered, WriteChunks};

class Output extends \web\io\Output {
  private $socket, $version;

  /**
   * Creates an output
   *
   * @param  peer.Socket $socket
   * @param  string $version
   */
  public function __construct($socket, $version= '1.1') {
    $this->socket= $socket;
    $this->version= $version;
  }

  /**
   * Uses chunked TE for HTTP/1.1, buffering for HTTP/1.0 
   *
   * @return web.io.Output
   * @see    https://tools.ietf.org/html/rfc2068#section-19.7.1
   */
  public function stream() {
    return $this->version < '1.1' ? new Buffered($this) : new WriteChunks($this);
  }

  /**
   * Begins output
   *
   * @param  int $status
   * @param  string $message
   * @param  [:string] $headers
   * @return void
   */
  public function begin($status, $message, $headers) {
    $this->socket->write(sprintf("HTTP/%s %d %s\r\n", $this->version, $status, $message));
    foreach ($headers as $name => $header) {
      foreach ($header as $value) {
        $this->socket->write($name.': '.$value."\r\n");
      }
    }
    $this->socket->write("\r\n");
  }

  /**
   * Writes the bytes.
   *
   * @param  string $bytes
   * @return void
   */
  public function write($bytes) {
    $this->socket->write($bytes);
  }
}