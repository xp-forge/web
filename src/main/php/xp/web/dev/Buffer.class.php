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
   * @param  web.Response $res
   * @return void
   */
  public function drain($res) {
    $res->answer($this->status, $this->message);
    foreach ($this->headers as $name => $value) {
      $res->header($name, $value);
    }

    $out= $res->stream(strlen($this->bytes));
    try {
      $out->write($this->bytes);
    } finally {
      $out->close();
    }
  }
}