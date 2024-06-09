<?php namespace web\io;

/**
 * Writes content with length by adding `Content-Length` header
 *
 * @test  web.unittest.io.WriteLengthTest
 */
class WriteLength extends Output {
  private $target, $length;

  /**
   * Creates a new instance
   *
   * @param  parent $target
   * @param  int $length
   */
  public function __construct($target, $length) {
    $this->target= $target;
    $this->length= $length;
  }

  /**
   * Begins output
   *
   * @param  int $status
   * @param  string $message
   * @param  [:string] $headers
   * @return void
   */
  public function begin($status, $message, $headers) {
    $headers['Content-Length']= [$this->length];
    $this->target->begin($status, $message, $headers);
  }

  /**
   * Writes a chunk of data
   *
   * @param  string $chunk
   * @return void
   */
  public function write($chunk) {
    $this->target->write($chunk);
  }

  /** @return void */
  public function flush() {
    $this->target->flush();
  }

  /** @return void */
  public function finish() {
    $this->target->finish();
  }
}