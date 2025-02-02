<?php namespace web\io;

/**
 * Input for testing purposes
 *
 * @test  xp://web.unittest.io.TestInputTest
 */
class TestInput implements Input {
  private $method, $resource, $headers, $body;
  private $incoming= null;

  /**
   * Creates a new instance
   *
   * @param  string $method
   * @param  string $resource
   * @param  [:string] $headers
   * @param  string|[:string] $body
   */
  public function __construct($method, $resource, $headers= [], $body= '') {
    $this->method= $method;
    $this->resource= $resource;
    $this->headers= $headers;

    if (is_array($body)) {
      $this->body= http_build_query($body);
      $this->headers+= ['Content-Type' => 'application/x-www-form-urlencoded'];
    } else {
      $this->body= $body;
    }

    $l= strlen($this->body);
    if ($l > 0 && !isset($this->headers['Transfer-Encoding'])) {
      $this->headers+= ['Content-Length' => $l];
    }
  }

  /** @return string */
  public function version() { return '1.1'; }

  /** @return string */
  public function scheme() { return 'http'; }

  /** @return string */
  public function method() { return $this->method; }

  /** @return string */
  public function resource() { return $this->resource; }

  /** @return iterable */
  public function headers() { return $this->headers; }

  /** @return ?io.streams.InputStream */
  public function incoming() {
    if (null === $this->incoming) {

      // Check Content-Length first, this is the typical case
      if (isset($this->headers['Content-Length'])) {
        return $this->incoming= new ReadLength($this, (int)$this->headers['Content-Length']);
      }

      // Check Transfer-Encoding. The special value "streamed" is used to test PHP
      // SAPIs, which take care of parsing chunked transfer encoding themselves.
      $te= $this->headers['Transfer-Encoding'] ?? null;
      if ('chunked' === $te) {
        return $this->incoming= new ReadChunks($this);
      } else if ('streamed' === $te) {
        return $this->incoming= new ReadStream($this);
      }

      // Neither Content-Length nor Transfer-Encoding; no body seems to have been passed
    }
    return $this->incoming;
  }

  /** @return ?string */
  public function readLine() {
    if ('' === $this->body) return null; // EOF

    $p= strpos($this->body, "\r\n");
    if (false === $p) {
      $return= $this->body;
      $this->body= '';
    } else {
      $return= substr($this->body, 0, $p);
      $this->body= (string)substr($this->body, $p + 2);
    }
    return $return;
  }

  /**
   * Reads a given number of bytes
   *
   * @param  int $length Pass -1 to read all
   * @return string
   */
  public function read($length= -1) {
    if (-1 === $length) {
      $return= $this->body;
      $this->body= '';
    } else {
      $return= substr($this->body, 0, $length);
      $this->body= substr($this->body, $length);
    }
    return $return;
  }

  /**
   * Returns parts from a multipart/form-data request
   *
   * @param  string $boundary
   * @return iterable
   */
  public function parts($boundary) {
    return new Parts($this->incoming(), $boundary);
  }
}