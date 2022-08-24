<?php namespace web\io;

use io\streams\OutputStream;

/**
 * Base class for all output implementations. Subclasses implement the
 * `begin` and `write` methods, and may decide to implement `flush`,
 * `finish` and `stream`.
 *
 * @see   xp.web.SAPI
 * @see   xp.web.srv.Output
 * @test  web.unittest.io.OutputTest
 */
abstract class Output implements OutputStream {
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
   * time of starting the output.
   *
   * @return self
   */
  public function stream() { return $this; }

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