<?php namespace xp\web\srv;

use lang\FormatException;
use peer\CryptoSocket;
use web\Headers;
use web\io\{ReadChunks, ReadLength, Parts, Input as IOInput};

class Input implements IOInput {
  const CLOSE   = 0;
  const REQUEST = 1;

  public $kind;
  private $socket;
  private $method, $uri, $version;
  private $buffer= null;
  private $incoming= null;

  /**
   * Creates a new input instance which reads from a socket.
   *
   * @param  peer.Socket $socket
   */
  public function __construct($socket) {

    // If we instantly get an EOF while reading, it's either a preconnect
    // or a kept-alive socket being closed.
    if ('' === ($initial= $socket->readBinary())) {
      $this->kind= self::CLOSE;
      return;
    }

    // Read status line cautiously. If a client does not send complete line
    // with the initial write (which it typically does), wait for another
    // 100 milliseconds. If no more data is transmitted, give up.
    if (false === ($p= strpos($initial, "\r\n"))) {
      if ($socket->canRead(0.1)) {
        $initial.= $socket->readBinary();
      }
    }

    if (3 === sscanf($initial, "%s %s HTTP/%[0-9.]\r\n", $this->method, $this->uri, $this->version)) {
      $this->buffer= substr($initial, $p + 2);
      $this->socket= $socket;
      $this->kind= self::REQUEST;
    } else {
      $this->kind= rtrim($initial);
    }
  }

  /** @return string */
  public function readLine() {
    if (null === $this->buffer) return null;    // EOF

    while (false === ($p= strpos($this->buffer, "\r\n"))) {
      $chunk= $this->socket->read();
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
  public function version() { return $this->version; }

  /** @return string */
  public function method() { return $this->method; }

  /** @return sring */
  public function uri() { return $this->uri; }

  /** @return iterable */
  public function headers() {
    yield 'Remote-Addr' => $this->socket->remoteEndpoint()->getHost();

    while ($line= $this->readLine()) {
      sscanf($line, "%[^:]: %[^\r]", $name, $value);

      if (null !== $this->incoming) {
        // Already determined whether an incoming payload is available
      } else if (0 === strncasecmp($name, 'Transfer-Encoding', 17) && 'chunked' === $value) {
        $this->incoming= new ReadChunks($this);
      } else if (0 === strncasecmp($name, 'Content-Length', 14)) {
        $this->incoming= new ReadLength($this, (int)$value);
      }

      yield $name => $value;
    }
  }

  /** @return ?io.streams.InputStream */
  public function incoming() { return $this->incoming; }

  /**
   * Reads a given number of bytes
   *
   * @param  int $length Pass -1 to read all
   * @return string
   */
  public function read($length= -1) {
    if (-1 === $length) {
      $data= $this->buffer;
      while (!$this->socket->eof()) {
        $data.= $this->socket->readBinary();
      }
      $this->buffer= null;
    } else if (strlen($this->buffer) >= $length) {
      $data= substr($this->buffer, 0, $length);
      $this->buffer= substr($this->buffer, $length);
    } else {
      $data= $this->buffer;
      $eof= false;
      while (strlen($data) < $length) {
        $data.= $this->socket->readBinary($length - strlen($data));
        if ($eof= $this->socket->eof()) break;
      }
      $this->buffer= $eof ? null : '';
    }
    return $data;
  }

  /**
   * Returns parts from a multipart/form-data request
   *
   * @param  string $boundary
   * @return iterable
   */
  public function parts($boundary) {
    return new Parts($this->incoming, $boundary);
  }
}