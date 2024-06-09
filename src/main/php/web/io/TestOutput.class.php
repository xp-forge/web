<?php namespace web\io;

use lang\XPClass;

/**
 * Input for testing purposes
 *
 * @test  web.unittest.io.TestOutputTest
 */
class TestOutput extends Output {
  private $stream;
  private $bytes= '';

  /** @param string|lang.XPClass $stream */
  public function __construct($stream= null) {
    if (null === $stream) {
      $this->stream= new XPClass(WriteChunks::class);
    } else if ($stream instanceof XPClass) {
      $this->stream= $stream;
    } else {
      $this->stream= XPClass::forName($stream);
    }
  }

  /**
   * Creates a new buffered test output
   *
   * @return self
   */
  public static function buffered() { return new self(Buffered::class); }

  /**
   * Creates a new chunked test output
   *
   * @return self
   */
  public static function chunked() { return new self(WriteChunks::class); }

  /**
   * Begins a request
   *
   * @param  int $status
   * @param  string $message
   * @param  [:string] $headers
   */
  public function begin($status, $message, $headers) {
    $this->bytes.= sprintf("HTTP/1.1 %d %s\r\n", $status, $message);
    foreach ($headers as $name => $header) {
      foreach ($header as $value) {
        $this->bytes.= $name.': '.$value."\r\n";
      }
    }
    $this->bytes.= "\r\n";
  }

  /**
   * Returns writer with length if known, using the configured stream otherwise
   *
   * @param  ?int $length
   * @return web.io.Output
   */
  public function stream($length= null) {
    return null === $length ? $this->stream->newInstance($this) : new WriteLength($this, $length);
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

  /** @return string */
  public function start() {
    return substr($this->bytes, 0, strpos($this->bytes, "\r\n"));
  }

  /** @return string */
  public function headers() {
    $start= strpos($this->bytes, "\r\n") + 2;
    return substr($this->bytes, $start, strpos($this->bytes, "\r\n\r\n") - $start);
  }

  /** @return string */
  public function body() {
    return substr($this->bytes, strpos($this->bytes, "\r\n\r\n") + 4);
  }
}