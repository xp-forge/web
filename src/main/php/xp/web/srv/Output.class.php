<?php namespace xp\web\srv;

use peer\SocketException;
use web\io\{Buffered, WriteChunks, Output as Base};

class Output extends Base {
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
    try {
      $this->socket->write($bytes);
    } catch (SocketException $e) {

      // See how the SocketException error message is constructed in Socket::write() at
      // https://github.com/xp-framework/networking/blob/v10.4.0/src/main/php/peer/Socket.class.php#L359
      $message= $e->getMessage();
      $p= strpos($message, ': ');
      throw new CannotWrite(false === $p ? $message : substr($message, $p + 2), $e);
    }
  }
}