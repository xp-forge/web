<?php namespace web\unittest;

use peer\SocketEndpoint;

class Channel {
  private static $i= 0;

  public $in, $out;
  public $closed= false;
  public $timeout= 60.0;

  public function __construct($chunks) { $this->in= $chunks; $this->out= []; $this->handle= ++self::$i; }

  public function remoteEndpoint() { return new SocketEndpoint('127.0.0.1', 6666); }

  public function canRead($timeout= 0.0) { return !empty($this->in); }

  public function readBinary($maxLen= 4096) { return array_shift($this->in); }

  public function write($chunk) { $this->out[]= $chunk; }

  public function close() { $this->closed= true; }

  public function eof() { return $this->closed || empty($this->in); }

  public function setTimeout($timeout) { $this->timeout= $timeout; }

  public function getHandle() { return $this->handle; }
}