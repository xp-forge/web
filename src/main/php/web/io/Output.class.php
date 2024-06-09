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
   * @param  ?int $length
   * @return self
   */
  public function stream($length= null) { return $this; }

  /** @return void */
  public function finish() { }

  /** @return void */
  public function flush() { }

  /** @return void */
  public final function close() {
    if ($this->closed) return;
    $this->finish();
    $this->closed= true;
  }

  /** Ensures `close()` is called */
  public function __destruct() {
    $this->close();
  }
}