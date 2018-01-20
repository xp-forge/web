<?php namespace web;

use util\URI;
use web\io\Input;
use web\io\ReadLength;
use web\io\ReadChunks;
use io\streams\MemoryInputStream;
use io\streams\Streams;
use lang\Value;
use util\Objects;

class Request implements Value {
  private $stream= null;
  private $lookup= [];
  private $headers= [];
  private $values= [];
  private $encoding= null;
  private $params= null;
  private $cookies= null;
  private $method, $uri, $input;

  /** @param web.io.Input $input */
  public function __construct(Input $input) {
    foreach ($input->headers() as $name => $value) {
      $lookup= strtolower($name);
      if (isset($this->lookup[$lookup])) {
        $this->headers[$this->lookup[$lookup]][]= $value;
      } else {
        $this->headers[$name]= (array)$value;
        $this->lookup[$lookup]= $name;
      }
    }

    $this->method= $input->method();
    $this->uri= new URI($input->scheme().'://'.$this->header('Host', 'localhost').$input->uri());
    $this->input= $input;
  }

  /**
   * Pass a named value along with this request
   *
   * @param  string $name
   * @param  var $value
   * @return self
   */
  public function pass($name, $value) {
    $this->values[$name]= $value;
    return $this;
  }

  /**
   * Rewrite request URI
   *
   * @param  string|util.URI $uri
   * @return self
   */
  public function rewrite($uri) {
    $this->uri= $this->uri->resolve($uri instanceof URI ? $uri : new URI($uri));
    return $this;
  }

  /**
   * Encode a parameter's value to XP encoding
   *
   * @param  var $param
   * @return var
   */
  private function encode($param) {
    if (is_array($param)) {
      foreach ($param as &$value) {
        $value= $this->encode($value);
      }
      return $param;
    } else if (null === $param) {
      return null;
    } else {
      return iconv($this->encoding, \xp::ENCODING, $param);
    }
  }

  /** @return string */
  public function method() { return $this->method; }

  /** @return util.URI */
  public function uri() { return $this->uri; }

  /** @return [:string|string[]] */
  public function headers() {
    $r= [];
    foreach ($this->headers as $name => $header) {
      $r[$name]= 1 === sizeof($header) ? $header[0] : $header;
    }
    return $r;
  }

  /**
   * Gets a header by name
   *
   * @param  string $name
   * @param  var $default
   * @return var
   */
  public function header($name, $default= null) {
    $lookup= strtolower($name);
    return isset($this->lookup[$lookup])
      ? implode(', ', $this->headers[$this->lookup[$lookup]])
      : $default
    ;
  }

  /** @return io.streams.InputStream */
  public function stream() {
    if (null !== $this->stream) {
      return $this->stream;
    } else if ('chunked' === $this->header('Transfer-Encoding')) {
      return $this->stream= new ReadChunks($this->input);
    } else if (null !== ($l= $this->header('Content-Length'))) {
      return $this->stream= new ReadLength($this->input, $l);
    } else {
      return null;
    }
  }

  /**
   * Parses payload into parameters and handles encoding (both cached)
   *
   * @see    http://www.w3.org/TR/html5/forms.html#application/x-www-form-urlencoded-encoding-algorithm
   * @return void
   */
  private function parse() {
    if (null !== $this->params) return;

    // Merge parameters from URL and urlencoded payload.
    $query= $this->uri->query(false);
    $type= new ContentType($this->header('Content-Type'));
    if ($type->matches('application/x-www-form-urlencoded')) {
      $data= Streams::readAll($this->stream());
      $this->stream= new MemoryInputStream($data);
      $query.= '&'.$data;
    }
    parse_str($query, $this->params);

    // Be liberal in what we accept and support "; charset=XXX" although the spec
    // states this media type does not have parameters! Then, handle the special
    // parameter named "_charset_" as per spec.
    if ($this->encoding= $type->param('charset', null)) {
      return;
    } else if (isset($this->params['_charset_'])) {
      $this->encoding= $this->params['_charset_'];
      unset($this->params['_charset_']);
      return;
    }

    // Otherwise, detect encoding.
    $this->encoding= 'utf-8';

    $offset= 0;
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

  /**
   * Gets request parameters
   *
   * @return [:var]
   */
  public function params() {
    $this->parse();

    $result= [];
    foreach ($this->params as $name => $param) {
      $result[$name]= $this->encode($param);
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
    $this->parse();

    return isset($this->params[$name]) ? $this->encode($this->params[$name]) : $default;
  }

  /**
   * Gets request values
   *
   * @return [:string]
   */
  public function values() { return $this->values; }

  /**
   * Gets a value by name
   *
   * @param  string $name
   * @param  var $default
   * @return var
   */
  public function value($name, $default= null) {
    return isset($this->values[$name]) ? $this->values[$name] : $default;
  }

  /**
   * Gets request cookies
   *
   * @return [:string]
   */
  public function cookies() {
    if (null === $this->cookies) {
      $this->cookies= [];
      if ($header= $this->header('Cookie')) {
        foreach (explode(';', $header) as $cookie) {
          sscanf(ltrim($cookie), '%[^=]=%[^;]', $name, $value);
          $this->cookies[urldecode($name)]= urldecode($value);
        }
      }
    }
    return $this->cookies;
  }

  /**
   * Gets a cookie by name
   *
   * @param  string $name
   * @param  var $default
   * @return var
   */
  public function cookie($name, $default= null) {
    if (null === $this->cookies) {
      $this->cookies();
    }

    return isset($this->cookies[$name]) ? $this->cookies[$name] : $default;
  }

  /**
   * Consumes rest of data and returns how many bytes were consumed
   *
   * @return int
   */
  public function consume() {
    $stream= $this->stream();
    if (null === $this->stream) {
      return -1;
    } else {
      $r= 0;
      while ($stream->available()) {
        $r+= strlen($stream->read());
      }
      return $r;
    }
  }

  /** @return string */
  public function toString() {
    return nameof($this).'('.$this->method.' '.$this->uri->toString().')@'.Objects::stringOf($this->headers);
  }

  /** @return string */
  public function hashCode() { return uniqid(microtime(true)); }

  /**
   * Compares this request
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) { return $value === $this ? 0 : 1; }
}