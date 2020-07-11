<?php namespace web\io;

use io\streams\InputStream;

class ReadStream implements InputStream {
  private $input, $available;

  /**
   * Creates a new reader for a given length
   *
   * @param  web.io.Input $input
   */
  public function __construct(Input $input) {
    $this->input= $input;
    $this->available= 1;
  }

  /**
   * Reads next chunk
   *
   * @param  int $limit
   * @return string
   */
  public function read($limit= 8192) {
    $chunk= $this->input->read($limit);
    $this->available= strlen($chunk);
    return $chunk;
  }

  /** @return int */
  public function available() { return $this->available; }

  /** @return void */
  public function close() { /* NOOP */  }
}