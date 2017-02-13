<?php namespace web;

use peer\URL;

class Request {
  private $lookup= [];
  private $headers= [];
  private $values= [];

  public function __construct($method, $uri, $stream= null) {
    $this->method= $method;
    $this->uri= $uri instanceof URL ? $uri : new URL($uri);
    if ($this->stream= $stream) {
      foreach ($stream->headers() as $name => $value) {
        $this->headers[$name]= $value;
        $this->lookup[strtolower($name)]= $name;
      }
    }
  }

  /** @return string */
  public function method() { return $this->method; }

  /** @return peer.URL */
  public function uri() { return $this->uri; }

  public function pass($name, $value) {
    $this->values[$name]= $value;
  }

  public function headers() { return $this->headers; }

  public function header($name) {
    $name= strtolower($name);
    return isset($this->lookup[$name]) ? $this->headers[$this->lookup[$name]] : null;
  }

  public function params() { return $this->uri->getParams(); }

  public function param($name) { }

  public function values() {return $this->values; }

  public function value($name) {
    return isset($this->values[$name]) ? $this->values[$name] : null;
  }
}