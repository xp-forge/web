<?php namespace web\io;

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
   * Reads next chunk
   *
   * @param  int $limit
   * @return string
   */
  public function read($limit= 8192) {
    $chunk= $this->input->read(min($limit, $this->remaininig));
    $this->remaininig-= strlen($chunk);
    return $chunk;
  }

  /** @return int */
  public function available() { return $this->remaininig; }

  /** @return void */
  public function close() { /* NOOP */  }
}