<?php namespace web;

use lang\IllegalStateException;
use web\io\WriteChunks;

/**
 * Response
 *
 * @test  xp://web.unittest.ResponseTest
 */
class Response {
  private $output;
  private $flushed= false;
  private $status= 200;
  private $message= 'OK';
  private $cookies= [];
  private $headers= [];

  /** @param web.io.Output $output */
  public function __construct($output= null) {
    $this->output= $output;
  }

  /**
   * Sets status code and optionally a message
   *
   * @param  int $status
   * @param  string $message
   * @return void
   */
  public function answer($status, $message= null) {
    $this->status= $status;
    $this->message= $message ?: Status::message($status);
  }

  /**
   * Sets a cookie
   *
   * @param  web.Response $cookie
   * @return void
   */
  public function cookie(Cookie $cookie) {
    $this->cookies[]= $cookie;
  }

  /**
   * Sets a header
   *
   * @param  string $name
   * @param  string $value Pass NULL to remove the header
   * @param  bool $append Append header if already existant
   * @return void
   */
  public function header($name, $value, $append= false) {
    if (null === $value) {
      unset($this->headers[$name]);
    } else if ($append) {
      $this->headers[$name][]= $value;
    } else if (is_array($value)) {
      $this->headers[$name]= [];
      foreach ($value as $v) {
        $this->headers[$name][]= (string)$v;
      }
    } else {
      $this->headers[$name]= [(string)$value];
    }
  }

  /** @return int */
  public function status() { return $this->status; }

  /** @return string */
  public function message() { return $this->message; }

  /** @return web.io.Output */
  public function output() { return $this->output; }

  /** @return bool */
  public function flushed() { return $this->flushed; }

  /** @return web.Cookie[] */
  public function cookies() { return $this->cookies; }

  /** @return [:string|string[]] */
  public function headers() {
    $r= [];
    foreach ($this->headers as $name => $header) {
      $r[$name]= 1 === sizeof($header) ? $header[0] : $header;
    }
    return $r;
  }

  /** @param web.io.Output $output */
  private function begin($output) {
    $output->begin($this->status, $this->message, $this->cookies
      ? array_merge($this->headers, ['Set-Cookie' => array_map(function($c) { return $c->header(); }, $this->cookies)])
      : $this->headers
    );
    $this->flushed= true;
  }

  /**
   * Sends headers
   *
   * @return void
   * @throws lang.IllegalStateException
   */
  public function flush($output= null) {
    if ($this->flushed) {
      throw new IllegalStateException('Response already flushed');
    }

    $this->begin($output ?: $this->output);
  }

  /**
   * Ends reponse, ensuring headers are sent and output is closed.
   *
   * Takes care of signalling zero length content if no transmission
   * has occured.
   *
   * @return void
   */
  public function end() {
    if (!$this->flushed) {
      if (!isset($this->headers['Content-Length']) && !isset($this->headers['Transfer-Encoding'])) {
        $this->headers['Content-Length']= [0];
      }

      $this->begin($this->output);
    }
    $this->output->close();
  }

  /**
   * Returns a stream to write on
   *
   * @param  int $size If omitted, uses chunked transfer encoding
   * @return io.streams.OutputStream
   */
  public function stream($size= null) {
    if (null === $size) {
      $output= $this->output->stream();
    } else {
      $this->headers['Content-Length']= [$size];
      $output= $this->output;
    }

    $this->flush($output);
    return $output;
  }

  /**
   * Transfers a stream
   *
   * @param  io.streams.InputStream $in
   * @param  string $mediaType
   * @param  int $size If omitted, uses chunked transfer encoding
   */
  public function transfer($in, $mediaType= 'application/octet-stream', $size= null) {
    $this->headers['Content-Type']= [$mediaType];

    $out= $this->stream($size);
    try {
      while ($in->available()) {
        $out->write($in->read());
      }
    } finally {
      $out->close();
      $in->close();
    }
  }


  /**
   * Sends some content
   *
   * @param  string $content
   * @param  string $mediaType
   */
  public function send($content, $mediaType= 'text/html') {
    $this->headers['Content-Type']= [$mediaType];

    $out= $this->stream(strlen($content));
    try {
      $out->write($content);
    } finally {
      $out->close();
    }
  }
}