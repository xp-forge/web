<?php namespace web\io;

/**
 * Writes Chunked transfer encoding
 *
 * @see   https://tools.ietf.org/html/rfc7230#section-4.1
 */
class WriteChunks extends Output {
  private $target;

  /** @param io.streams.OutputStream $target */
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
    $this->target->begin($status, $message, $headers);
  }

  /**
   * Writes a chunk of data
   *
   * @param  string $chunk
   * @return void
   */
  public function write($chunk) {
    $this->target->write(dechex(strlen($chunk))."\r\n".$chunk."\r\n");
  }

  /** @return void */
  public function finish() {
    $this->target->write("0\r\n\r\n");
    $this->target->finish();
  }
}