<?php namespace web\io;

/**
 * Input for testing purposes
 *
 * @test  xp://web.unittest.io.TestInputTest
 */
class TestInput implements Input {
  private $method, $uri, $headers, $body;

  /**
   * Creates a new instance
   *
   * @param  string $method
   * @param  string $uri
   * @param  [:string] $headers
   * @param  string|[:string] $body
   */
  public function __construct($method, $uri, $headers= [], $body= '') {
    $this->method= $method;
    $this->uri= $uri;
    $this->headers= $headers;

    if (is_array($body)) {
      $this->body= http_build_query($body);
      $this->headers+= ['Content-Type' => 'application/x-www-form-urlencoded'];
    } else {
      $this->body= $body;
    }

    if (strlen($this->body) > 0 && !isset($this->headers['Transfer-Encoding'])) {
      $this->headers+= ['Content-Length' => strlen($this->body)];
    }
  }

  /** @return string */
  public function version() { return '1.1'; }

  /** @return string */
  public function scheme() { return 'http'; }

  /** @return string */
  public function method() { return $this->method; }

  /** @return string */
  public function uri() { return $this->uri; }

  /** @return iterable */
  public function headers() { return $this->headers; }

  /** @return ?string */
  public function readLine() {
    $p= strpos($this->body, "\n");
    if (false === $p) {
      $this->body= '';
      return null;
    } else {
      $return= substr($this->body, 0, $p);
      $this->body= (string)substr($this->body, $p + 1);
      return $return;
    }
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
}