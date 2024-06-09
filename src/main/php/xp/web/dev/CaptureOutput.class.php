<?php namespace xp\web\dev;

use web\io\Output;

class CaptureOutput extends Output {
  public $status, $message, $headers;
  public $bytes= '';
  private $length= -1;

  /**
   * Sets length
   *
   * @param  ?int $length
   * @return self
   */
  public function length($length) {
    $this->length= $length;
    return $this;
  }

  /**
   * Begins output
   *
   * @param  int $status
   * @param  string $message
   * @param  [:string] $headers
   * @return void
   */
  public function begin($status, $message, $headers) {
    $this->status= $status;
    $this->message= $message;
    $this->headers= $headers;
  }

  /**
   * Writes a chunk of data
   *
   * @param  string $chunk
   * @return void
   */
  public function write($bytes) {
    $this->bytes.= $bytes;
  }

  /**
   * Ensure response is flushed
   *
   * @param  web.Response $response
   * @return void
   */
  public function end($response) {
    if (-1 === $this->length) $response->flush();
  }

  /**
   * Drain this buffered output to a given output instance, closing it
   * once finished.
   *
   * @param  web.Response $response
   * @return void
   */
  public function drain($response) {
    $out= $response->output()->stream($this->length);
    try {
      $out->begin($this->status, $this->message, $this->headers);
      $out->write($this->bytes);
    } finally {
      $out->close();
    }
  }
}