<?php namespace web\unittest\handler;

class TestInput {
  private $headers;

  public function __construct($headers= []) {
    $this->headers= $headers;
  }

  public function headers() {
    return $this->headers;
  }
}