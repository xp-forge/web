<?php namespace web\unittest;

class TestInput implements \web\io\Input {
  private $method, $uri, $headers, $body;

  public function __construct($method, $uri, $headers= [], $body= '') {
    $this->method= $method;
    $this->uri= $uri;
    $this->headers= $headers;
    $this->body= $body;
  }

  public function scheme() { return 'http'; }

  public function method() { return $this->method; }

  public function uri() { return $this->uri; }

  public function headers() { return $this->headers; }

  public function read($length= -1) {
    if (-1 === $length) {
      $return= $this->body;
      $this->body= '';
    } else {
      $return= substr($this->body, 0, $length);
      $this->body= substr($this->body, $length + 1);
    }
    return $return;
  }
}