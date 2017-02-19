<?php namespace web\unittest\handler;

class TestInput implements \web\io\Input {
  private $headers;

  public function __construct($headers= []) {
    $this->headers= $headers;
  }

  public function headers() {
    return $this->headers;
  }
}