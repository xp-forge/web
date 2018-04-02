<?php namespace web\io;

use lang\XPClass;

class TestOutput extends Output {
  private $bytes;
  private $streaming;

  /** Create a new Test Output */
  public function __construct() {
    $this->streaming= new XPClass(WriteChunks::class);
  }

  /**
   * Use streaming class, which defaults to `WriteChunks`
   *
   * @param  string|lang.XPClass $streaming
   * @return self
   */
  public function using($streaming) {
    $this->streaming= $streaming instanceof XPClass ? $streaming : XPClass::forName($streaming);
    return $this;
  }

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

  /** @return web.io.Output */
  public function streaming() { return $this->streaming->newInstance($this); }

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