<?php namespace web\unittest;

use peer\SocketEndpoint;

class Channel {
  public $in, $out;
  public $closed= false;

  public function __construct($chunks) { $this->in= $chunks; }

  public function remoteEndpoint() { return new SocketEndpoint('127.0.0.1', 6666); }

  public function canRead($timeout= 0.0) { return !empty($this->in); }

  public function read($maxLen= 4096) { return array_shift($this->in); }

  public function readBinary($maxLen= 4096) { return array_shift($this->in); }

  public function write($chunk) { $this->out[]= $chunk; }

  public function close() { $this->closed= true; }
}