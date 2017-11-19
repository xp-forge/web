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
    } else {
      $this->headers[$name]= [$value];
    }
  }

  /** @return int */
  public function status() { return $this->status; }

  /** @return string */
  public function message() { return $this->message; }

  /** @return [:string] */
  public function headers() { return $this->headers; }

  /** @return web.io.Output */
  public function output() { return $this->output; }

  /** @return bool */
  public function flushed() { return $this->flushed; }

  /**
   * Sends headers
   *
   * @return void
   * @throws lang.IllegalStateException
   */
  public function flush() {
    if ($this->flushed) {
      throw new IllegalStateException('Response already flushed');
    }

    $this->output->begin($this->status, $this->message, $this->headers);
    $this->flushed= true;
  }

  /**
   * Returns a stream to write on
   *
   * @param  int $size If omitted, uses chunked transfer encoding
   * @return io.streams.OutputStream
   */
  public function stream($size= null) {
    if (null === $size) {
      $this->headers['Transfer-Encoding']= ['chunked'];
      $output= new WriteChunks($this->output);
    } else {
      $this->headers['Content-Length']= [$size];
      $output= $this->output;
    }

    $this->flush();
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