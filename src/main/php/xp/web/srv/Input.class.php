<?php namespace xp\web\srv;

use lang\FormatException;
use peer\{CryptoSocket, SocketTimeoutException};
use web\Headers;
use web\io\{ReadChunks, ReadLength, Parts, Input as Base};

class Input implements Base {
  const REQUEST    = 0;
  const CLOSE      = 1;
  const INCOMPLETE = 2;
  const EXCESSIVE  = 3;
  const TIMEOUT    = 4;

  public $kind= null;
  public $buffer= null;
  private $socket;
  private $method, $uri, $version;
  private $incoming= null;

  /**
   * Creates a new input instance which reads from a socket.
   *
   * @param  peer.Socket $socket
   */
  public function __construct($socket) {
    $this->socket= $socket;
  }

  /**
   * Consumes status line and headers
   *
   * @param  int $limit defaults to 16 KB
   * @return iterable
   */
  public function consume($limit= 16384) {

    // If we instantly get an EOF while reading, it's either a preconnect
    // or a kept-alive socket being closed.
    if ('' === ($this->buffer= $this->socket->readBinary())) {
      return $this->kind= self::CLOSE;
    }

    // Read until we have the complete headers, imposing given length limit
    do {
      if (strlen($this->buffer) > $limit) {
        return $this->kind= self::EXCESSIVE;
      } else if (false !== strpos($this->buffer, "\r\n\r\n")) {
        break;
      }

      try {
        yield 'read' => null;
        $this->buffer.= $this->socket->readBinary();
      } catch (SocketTimeoutException $e) {
        return $this->kind= self::TIMEOUT;
      }
    } while (true);

    if (3 !== sscanf($this->buffer, "%s %s HTTP/%[0-9.]\r\n", $this->method, $this->uri, $this->version)) {
      return $this->kind= self::INCOMPLETE;
    }

    $this->buffer= substr($this->buffer, strpos($this->buffer, "\r\n") + 2);
    $this->kind= self::REQUEST;
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