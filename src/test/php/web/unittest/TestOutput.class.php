<?php namespace web\unittest;

class TestOutput extends \web\io\Output {
  public $bytes;

  public function begin($status, $message, $headers) {
    $this->bytes= sprintf("HTTP/1.1 %d %s\r\n", $status, $message);
    foreach ($headers as $name => $value) {
      $this->bytes.= $name.': '.$value."\r\n";
    }
    $this->bytes.= "\r\n";
  }

  public function write($bytes) {
    $this->bytes.= $bytes;
  }
}