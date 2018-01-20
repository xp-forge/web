<?php namespace web\io;

class TestOutput extends Output {
  private $bytes;

  /**
   * Begins a request
   *
   * @param  int $status
   * @param  string $message
   * @param  [:string] $headers
   */
  public function begin($status, $message, $headers) {
    $this->bytes= sprintf("HTTP/1.1 %d %s\r\n", $status, $message);
    foreach ($headers as $name => $header) {
      foreach ($header as $value) {
        $this->bytes.= $name.': '.$value."\r\n";
      }
    }
    $this->bytes.= "\r\n";
  }

  /**
   * Writes the bytes (in this case, to the internal buffer which can be
   * access via the `bytes()` method)
   *
   * @param  string $bytes
   * @return void
   */
  public function write($bytes) { $this->bytes.= $bytes; }

  /** @return string */
  public function bytes() { return $this->bytes; }
}