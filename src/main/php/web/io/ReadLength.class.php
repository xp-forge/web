<?php namespace web\io;

use io\IOException;
use io\streams\InputStream;

/**
 * Reads from an input with a given legth
 *
 * @test  xp://web.unittest.io.ReadLengthTest
 */
class ReadLength implements InputStream {
  private $input, $remaininig;

  /**
   * Creates a new reader for a given length
   *
   * @param  web.io.Input $input
   * @param  int $length
   */
  public function __construct(Input $input, $length) {
    $this->input= $input;
    $this->remaininig= $length;
  }

  /**
   * Reads next chunk. Raises an error if EOF is reached unexpectedly.
   *
   * @param  int $limit
   * @return string
   */
  public function read($limit= 8192) {
    if (0 === $this->remaininig) return '';

    $chunk= $this->input->read(min($limit, $this->remaininig));
    if ('' === $chunk) {
      $this->remaininig= 0;
      throw new IOException('EOF');
    }

    $this->remaininig-= strlen($chunk);
    return $chunk;
  }

  /** @return int */
  public function available() { return $this->remaininig; }

  /** @return void */
  public function close() { /* NOOP */  }
}