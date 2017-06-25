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
      $output= new WriteChunks($this->output);
    } else {
      $this->headers['Content-Length']= $size;
      $output= $this->output;
    }

    $output->begin($this->status, $this->message, $this->headers);
    $this->flushed= true;
    while ($in->available()) {
      $output->write($in->read());
    }

    $output->finish();
    $in->close();
  }

  public function send($content, $mediaType= 'text/html') {
    $this->headers['Content-Type']= $mediaType;
    $this->headers['Content-Length']= strlen($content);
    $this->flush();

    $this->output->write($content);
    $this->output->finish();
  }
}