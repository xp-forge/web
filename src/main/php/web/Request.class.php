<?php namespace web;

use peer\URL;

class Request {
  private $lookup= [];
  private $headers= [];
  private $values= [];
  private $encoding= null;

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

  private function encode($encoding, $param) {
    if (is_array($param)) {
      foreach ($param as &$value) {
        $value= $this->encode($encoding, $value);
      }
      return $param;
    } else if (null === $param) {
      return null;
    } else {
      return iconv($encoding, \xp::ENCODING, $param);
    }
  }

  /** @return string */
  public function encoding() {
    if (null === $this->encoding) {
      $this->encoding= 'utf-8';

      $offset= 0;
      $query= $this->uri->getQuery();
      while (false !== ($p= strpos($query, '%', $offset))) {
        $chr= hexdec($query{$p + 1}.$query{$p + 2});

        if ($chr < 0x80) {                              // OK, same as ASCII
          $offset= $p + 3;
          continue;
        } else if ($chr >= 0xc2 && $chr <= 0xdf) {      // 2 byte sequence
          $sequence= [substr($query, $p + 4, 2)];
          $offset= $p + 6;
        } else if ($chr >= 0xe0 && $chr <= 0xef) {      // 3 byte sequence
          $sequence= [substr($query, $p + 4, 2), substr($query, $p + 7, 2)];
          $offset= $p + 9;
        } else if ($chr >= 0xf0 && $chr <= 0xf4) {      // 4 byte sequence
          $sequence= [substr($query, $p + 4, 2), substr($query, $p + 7, 2), substr($query, $p + 10, 2)];
          $offset= $p + 12;
        } else {
          $this->encoding= 'iso-8859-1';
          break;
        }

        foreach ($sequence as $bytes) {
          $chr= hexdec($bytes);
          if ($chr < 0x80 || $chr > 0xbf) {
            $this->encoding= 'iso-8859-1';
            break 2;
          }
        }
      }
    }
    return $this->encoding;
  }

  /** @return string */
  public function method() { return $this->method; }

  /** @return peer.URL */
  public function uri() { return $this->uri; }

  public function pass($name, $value) {
    $this->values[$name]= $value;
  }

  /**
   * Gets request headers
   *
   * @return [:string]
   */
  public function headers() { return $this->headers; }

  /**
   * Gets a header by name
   *
   * @param  string $name
   * @param  var $default
   * @return var
   */
  public function header($name, $default= null) {
    $name= strtolower($name);
    return isset($this->lookup[$name]) ? $this->headers[$this->lookup[$name]] : $default;
  }

  /**
   * Gets request parameters
   *
   * @return [:var]
   */
  public function params() {
    $result= [];
    $encoding= $this->encoding();
    foreach ($this->uri->getParams() as $name => $param) {
      $result[$name]= $this->encode($encoding, $param);
    }
    return $result;
  }

  /**
   * Gets a request paramer by name
   *
   * @param  string $name
   * @param  var $default
   * @return var
   */
  public function param($name, $default= null) {
    return $this->encode($this->encoding(), $this->uri->getParam($name, $default));
  }

  public function values() { return $this->values; }

  public function value($name) {
    return isset($this->values[$name]) ? $this->values[$name] : null;
  }
}