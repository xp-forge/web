<?php namespace xp\web\dev;

use web\io\Output;

class Buffer extends Output {
  public $status, $message, $headers;
  public $bytes= '';

  public function begin($status, $message, $headers) {
    $this->status= $status;
    $this->message= $message;
    $this->headers= $headers;
  }

  public function write($bytes) {
    $this->bytes.= $bytes;
  }

  /**
   * Drain this buffered output to a given output instance, closing it
   * once finished.
   *
   * @param  web.io.Output $out
   * @return void
   */
  public function drain(Output $out) {
    $out->begin($this->status, $this->message, $this->headers);
    $out->write($this->bytes);
    $out->close();
  }
}