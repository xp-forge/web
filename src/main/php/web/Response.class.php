<?php namespace web;

use lang\IllegalStateException;
use web\io\WriteChunks;

/**
 * Response
 *
 * @test  xp://web.unittest.ResponseTest
 */
class Response {
  private $target;
  private $flushed= false;
  private $status= 200;
  private $message= 'OK';
  private $headers= [];

  public function __construct($target= null) {
    $this->target= $target;
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
   * @param  string $value
   * @return void
   */
  public function header($name, $value) {
    $this->headers[$name]= $value;
  }

  /** @return int */
  public function status() { return $this->status; }

  /** @return string */
  public function message() { return $this->message; }

  /** @return [:string] */
  public function headers() { return $this->headers; }

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

    $this->target->begin($this->status, $this->message, $this->headers);
    $this->flushed= true;
  }

  /**
   * Transfers a stream
   *
   * @param  io.streams.InputStream $in
   * @param  string $mediaType
   * @param  int $size If omitted, uses chunked transfer encoding
   */
  public function transfer($in, $mediaType= 'application/octet-stream', $size= null) {
    $this->headers['Content-Type']= $mediaType;
    if (null === $size) {
      $this->headers['Transfer-Encoding']= 'chunked';
      $target= new WriteChunks($this->target);
    } else {
      $this->headers['Content-Length']= $size;
      $target= $this->target;
    }

    $this->flush();
    while ($in->available()) {
      $target->write($in->read());
    }

    $target->finish();
    $in->close();
  }

  public function send($content, $mediaType= 'text/html') {
    $this->headers['Content-Type']= $mediaType;
    $this->headers['Content-Length']= strlen($content);
    $this->flush();

    $this->target->write($content);
    $this->target->finish();
  }
}