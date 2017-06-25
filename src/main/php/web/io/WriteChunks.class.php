<?php namespace web\io;

/**
 * Writes Chunked transfer encoding
 *
 * @see   https://tools.ietf.org/html/rfc2616#section-3.6.1
 */
class WriteChunks {
  private $target;

  /** @param io.streams.OutputStream $target */
  public function __construct($target) {
    $this->target= $target;
  }

  /** @param string $chunk */
  public function write($chunk) {
    $this->target->write(dechex(strlen($chunk))."\r\n".$chunk."\r\n");
  }

  /** @return void */
  public function close() {
    $this->target->write("0\r\n\r\n");
  }
}