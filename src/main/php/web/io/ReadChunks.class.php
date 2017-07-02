<?php namespace web\io;

class ReadChunks implements \io\streams\InputStream {
  private $input;
  private $available;
  private $buffer= '';

  /**
   * Scans a chunk, populating buffer and input
   *
   * @return int
   */
  private function scan() {
    while (false === ($p= strpos($this->buffer, "\n"))) {
      $this->buffer.= $this->input->read(0xff);
    }

    $this->available= hexdec(substr($this->buffer, 0, $p));
    return $p + 1;
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
    $p= $this->scan();
    while (strlen($this->buffer) - $p < $this->available) {
      $this->buffer.= $this->input->read($this->available - strlen($this->buffer) + $p);
    }

    $chunk= substr($this->buffer, $p, $this->available);
    $this->buffer= substr($this->buffer, $p + $this->available);
    return $chunk;
  }

  /** @return int */
  public function available() { return $this->available; }

  /** @return void */
  public function close() { /* NOOP */  }
}