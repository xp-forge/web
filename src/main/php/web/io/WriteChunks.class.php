<?php namespace web\io;

/**
 * Writes Chunked transfer encoding
 *
 * @see   https://tools.ietf.org/html/rfc7230#section-4.1
 * @test  web.unittest.io.WriteChunksTest
 */
class WriteChunks extends Output {
  const BUFFER_SIZE = 4096;

  private $target;
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
    $headers['Transfer-Encoding']= ['chunked'];
    $this->target->begin($status, $message, $headers);
  }

  /**
   * Writes a chunk of data
   *
   * @param  string $chunk
   * @return void
   */
  public function write($chunk) {
    $this->buffer.= $chunk;
    if (strlen($this->buffer) > self::BUFFER_SIZE) {
      $this->target->write(dechex(strlen($this->buffer))."\r\n".$this->buffer."\r\n");
      $this->buffer= '';
    }
  }

  /** @return void */
  public function flush() {
    if (strlen($this->buffer) > 0) {
      $this->target->write(dechex(strlen($this->buffer))."\r\n".$this->buffer."\r\n");
      $this->buffer= '';
      $this->target->flush();
    }
  }

  /** @return void */
  public function finish() {
    $this->flush();
    $this->target->write("0\r\n\r\n");
    $this->target->close();
  }
}