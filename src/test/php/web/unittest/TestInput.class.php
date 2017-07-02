<?php namespace web\unittest;

class TestInput implements \web\io\Input {
  private $method, $uri, $headers, $body;

  /**
   * Creates a new instance
   *
   * @param  string $method
   * @param  string $uri
   * @param  [:string] $headers
   * @param  string $body
   */
  public function __construct($method, $uri, $headers= [], $body= '') {
    $this->method= $method;
    $this->uri= $uri;
    $this->headers= $headers;
    $this->body= $body;
  }

  /** @return string */
  public function scheme() { return 'http'; }

  /** @return string */
  public function method() { return $this->method; }

  /** @return string */
  public function uri() { return $this->uri; }

  /** @return iterable */
  public function headers() { return $this->headers; }

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