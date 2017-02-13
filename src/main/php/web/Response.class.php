<?php namespace web;

use lang\IllegalStateException;

class Response implements \io\streams\OutputStream {
  private $target;
  private $flushed= false;
  private $status= 200;
  private $message= 'OK';
  private $headers= [];

  public function __construct($target= null) {
    $this->target= $target;
  }

  public function answer($status, $message) {
    $this->status= $status;
    $this->message= $message;
  }

  public function header($name, $value) {
    $this->headers[$name]= $value;
  }

  /** @return int */
  public function status() { return $this->status; }

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
   * Writes to response, flushing it if necessary.
   *
   * @return void
   */
  public function write($bytes) {
    $this->flushed || $this->flush();
    $this->target->write($bytes);
  }

  /**
   * Closes response
   *
   * @return void
   */
  public function close() {
    $this->flushed || $this->flush();
  }

  public function transfer($in, $mediaType= 'application/octet-stream', $size= -1) {
    $this->headers['Content-Type']= $mediaType;
    $this->headers['Content-Length']= $size;    // FIXME: Transfer-Encoding chunked!
    $this->flush();
    $this->target->stream($in);
  }

  public function send($content, $mediaType= 'text/html') {
    $this->headers['Content-Type']= $mediaType;
    $this->headers['Content-Length']= strlen($content);
    $this->flush();
    $this->target->write($content);
  }
}