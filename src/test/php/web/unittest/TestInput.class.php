<?php namespace web\unittest;

class TestInput implements \web\io\Input {
  private $method, $uri, $headers;

  public function __construct($method, $uri, $headers= []) {
    $this->method= $method;
    $this->uri= $uri;
    $this->headers= $headers;
  }

  public function method() { return $this->method; }

  public function uri() { return $this->uri; }

  public function headers() { return $this->headers; }
}