<?php namespace web\io;

use io\IOException;
use io\streams\InputStream;

/**
 * Reads chunked transfer encoding 
 *
 * @test  xp://web.unittest.io.ReadChunksTest
 * @see   https://tools.ietf.org/html/rfc7230#section-4.1
 */
class ReadChunks implements InputStream {
  private $input;
  private $remaining= null;
  private $buffer= '';

  /**
   * Scans a chunk, populating length and buffer
   *
   * @return int
   * @throws io.IOException for chunked format errors
   */
  private function scan() {
    $size= $this->input->readLine() ?? '';

    if (1 !== sscanf($size, '%x', $l)) {
      throw new IOException('No chunk segment present (`'.addcslashes($size, "\0..\37").'`)');
    }

    // Chunk with 0 length indicates EOF
    if ($l > 0) {
      $this->buffer.= $this->input->read($l);
      $this->remaining+= $l;
    } else {
      $this->remaining= 0;
    }

    $this->input->readLine();
    return $this->remaining;
  }

  /**
   * Creates a new reader
   *
   * @param  web.io.Input $input
   */
  public function __construct(Input $input) {
    $this->input= $input;
  }

  /**
   * Reads next line terminated by `<CRLF>`
   *
   * @return string
   */
  public function line() {
    while (false === ($p= strpos($this->buffer, "\r\n"))) {
      if (0 === $this->scan()) return $this->buffer;  // EOF
    }

    $chunk= substr($this->buffer, 0, $p);
    $length= strlen($chunk) + 2;
    $this->buffer= substr($this->buffer, $p + 2);

    if (0 === ($this->remaining-= $length)) {
      $this->scan();
    }
    return $chunk;
  }

  /**
   * Reads next chunk
   *
   * @param  int $limit
   * @return string
   */
  public function read($limit= 8192) {
    $remaining= $this->remaining ?? $this->scan();

    $chunk= substr($this->buffer, 0, min($limit, $remaining));
    if ('' === $chunk) {
      $this->remaining= 0;
      throw new IOException('EOF');
    }

    $length= strlen($chunk);
    $this->buffer= substr($this->buffer, $length);

    if (0 === ($this->remaining-= $length)) {
      $this->scan();
    }
    return $chunk;
  }

  /** @return int */
  public function available() {
    return $this->remaining ?? $this->scan();
  }

  /** @return void */
  public function close() { /* NOOP */  }
}