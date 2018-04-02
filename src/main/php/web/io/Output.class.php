<?php namespace web\io;

abstract class Output implements \io\streams\OutputStream {
  private $closed= false;

  /**
   * Begins output
   *
   * @param  int $status
   * @param  string $message
   * @param  [:string] $headers
   * @return void
   */
  public abstract function begin($status, $message, $headers);

  /**
   * Writes a chunk of data
   *
   * @param  string $chunk
   * @return void
   */
  public abstract function write($bytes);

  /**
   * Returns an output used when the content-length is not known at the
   * time of staring the output.
   *
   * @return web.io.Output
   */
  public function stream() { }

  /** @return void */
  public function finish() { }

  /** @return void */
  public function flush() { }

  /** @return void */
  public function close() {
    if ($this->closed) return;
    $this->finish();
    $this->closed= true;
  }
}