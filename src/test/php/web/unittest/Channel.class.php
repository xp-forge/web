<?php namespace web\unittest;

use peer\{Socket, SocketEndpoint};

class Channel extends Socket {
  public $in, $out, $closed;

  public function __construct($chunks, $connected= false) { $this->in= $chunks; $this->closed= !$connected; }

  public function connect($timeout= 2.0) { $this->closed= false; }

  public function isConnected() { return !$this->closed; }

  public function remoteEndpoint() { return new SocketEndpoint('127.0.0.1', 6666); }

  public function setTimeout($timeout) { }

  public function useNoDelay() { }

  public function canRead($timeout= 0.0) { return !empty($this->in); }

  public function read($maxLen= 4096) { return array_shift($this->in); }

  public function readBinary($maxLen= 4096) { return array_shift($this->in); }

  public function write($chunk) { $this->out[]= $chunk; }

  public function close() { $this->closed= true; }
}