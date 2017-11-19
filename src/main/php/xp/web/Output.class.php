<?php namespace xp\web;

class Output extends \web\io\Output {
  private $socket;

  public function __construct($socket) {
    $this->socket= $socket;
  }

  public function begin($status, $message, $headers) {
    $this->socket->write(sprintf("HTTP/1.1 %d %s\r\n", $status, $message));
    foreach ($headers as $name => $header) {
      foreach ($header as $value) {
        $this->socket->write($name.': '.$value."\r\n");
      }
    }
    $this->socket->write("\r\n");
  }

  public function write($bytes) {
    $this->socket->write($bytes);
  }
}