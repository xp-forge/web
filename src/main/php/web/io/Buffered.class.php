<?php namespace web\io;

/**
 * Buffers data
 */
class Buffered extends Output {
  private $target;
  private $status, $message, $headers;
  private $buffer= '';

  /** @param web.io.Output $target */
  public function __construct($target) {
    $this->target= $target;
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
    $this->status= $status;
    $this->message= $message;
    $this->headers= $headers;
  }

  /**
   * Writes a chunk of data
   *
   * @param  string $chunk
   * @return void
   */
  public function write($chunk) {
    $this->buffer.= $chunk;
  }

  /** @return void */
  public function finish() {
    $this->headers['Content-Length']= [strlen($this->buffer)];
    $this->target->begin($this->status, $this->message, $this->headers);
    $this->target->write($this->buffer);
    $this->target->finish();
  }
}