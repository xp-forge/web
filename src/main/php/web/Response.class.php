<?php namespace web;

use lang\IllegalStateException;
use web\io\WriteChunks;
use web\io\Body;

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
  private $body= null;

  /** @param web.io.Output|self $arg */
  public function __construct($arg= null) {
    if ($arg instanceof self) {
      $this->output= $arg->output;
      $this->flushed= $arg->flushed;
      $this->status= $arg->status;
      $this->message= $arg->message;
      $this->headers= $arg->headers;
      $this->body= $arg->body;
    } else {
      $this->output= $arg;
    }
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

  /** @param var $entity */
  public function entity($entity) { $this->body= new Body($entity, 'text/html'); }

  /** @return int */
  public function status() { return $this->status; }

  /** @return string */
  public function message() { return $this->message; }

  /** @return [:string] */
  public function headers() { return $this->headers; }

  /** @return web.io.Body */
  public function body() { return $this->body; }

  /** @return web.io.Output */
  public function output() { return $this->output; }

  /** @return bool */
  public function flushed() { return $this->flushed; }

  /**
   * Sends headers (and body, if any)
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

    if (null !== $this->body) {
      $this->send($this->body[0], $this->body[1]);
    }
  }

  /**
   * Returns a stream to write on
   *
   * @param  int $size If omitted, uses chunked transfer encoding
   * @return io.streams.OutputStream
   */
  public function stream($size= null) {
    if (null === $size) {
      $this->headers['Transfer-Encoding']= 'chunked';
      $output= new WriteChunks($this->output);
    } else {
      $this->headers['Content-Length']= $size;
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
    $this->headers['Content-Type']= $mediaType;

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
    $this->headers['Content-Type']= $mediaType;

    $out= $this->stream(strlen($content));
    try {
      $out->write($content);
    } finally {
      $out->close();
    }
  }
}