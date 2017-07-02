<?php namespace web\io;

class ReadLength implements \io\streams\InputStream {
  private $input, $available;

  /**
   * Creates a new reader for a given length
   *
   * @param  web.io.Input $input
   * @param  int $length
   */
  public function __construct(Input $input, $length) {
    $this->input= $input;
    $this->available= $length;
  }

  /**
   * Reads next chunk
   *
   * @param  int $limit
   * @return string
   */
  public function read($limit= 8192) {
    $chunk= $this->input->read(min($limit, $this->available));
    $this->available-= strlen($chunk);
    return $chunk;
  }

  /** @return int */
  public function available() { return $this->available; }

  /** @return void */
  public function close() { /* NOOP */  }
}