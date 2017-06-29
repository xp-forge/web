<?php namespace web\io;

abstract class Output implements \io\streams\OutputStream {
  private $closed= false;

  public abstract function begin($status, $message, $headers);

  public abstract function write($bytes);

  /** @return void */
  public function finish() { }

  /** @return void */
  public function flush() { }

  /** @return void */
  public function close() {
    if ($this->closed) return;
    $this->finish();
    $this->close= true;
  }
}