<?php namespace web\io;

use lang\FormatException;

/**
 * Reads chunked transfer encoding 
 *
 * @test  xp://web.unittest.io.ReadChunksTest
 * @see   https://tools.ietf.org/html/rfc7230#section-4.1
 */
class ReadChunks implements \io\streams\InputStream {
  private $input, $remaining, $length, $buffer;

  /**
   * Scans a chunk, populating length and buffer
   *
   * @return void
   */
  private function scan() {
    $size= $this->input->readLine();
    if (1 !== sscanf($size, "%x", $this->length)) {
      throw new FormatException('No chunk segment present');
    }

    $this->remaining= $this->length;
    $this->buffer= 0 === $this->length ? '' : $this->input->read($this->length);
    $this->input->readLine();
  }

  /**
   * Creates a new reader
   *
   * @param  web.io.Input $input
   */
  public function __construct(Input $input) {
    $this->input= $input;
    $this->scan();
  }

  /**
   * Reads next chunk
   *
   * @param  int $limit
   * @return string
   */
  public function read($limit= 8192) {
    $this->remaining || $this->scan();

    $chunk= substr($this->buffer, 0, min($limit, $this->remaining));
    $this->buffer= substr($this->buffer, strlen($chunk));
    $this->remaining-= strlen($chunk);
    return $chunk;
  }

  /** @return int */
  public function available() { return $this->length; }

  /** @return void */
  public function close() { /* NOOP */  }
}