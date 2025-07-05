<?php namespace xp\web\srv;

use lang\{IllegalStateException, Closeable};
use peer\Socket;

/** A single worker process */
class Worker implements Closeable {
  private $handle;
  public $socket;

  /**
   * Creates a new worker
   *
   * @param  resource $handle
   * @param  peer.Socket $socket
   */
  public function __construct($handle, $socket) {
    $this->handle= $handle;
    $this->socket= $socket;
  }

  /** @return ?int */
  public function pid() {
    return $this->handle ? proc_get_status($this->handle)['pid'] : null;
  }

  /** @return bool */
  public function running() {
    return $this->handle ? proc_get_status($this->handle)['running'] : false;
  }

  /**
   * Shuts down this worker
   * 
   * @throws lang.IllegalStateException
   * @return void
   */
  public function shutdown() {
    if (!$this->handle) throw new IllegalStateException('Worker not running');

    proc_terminate($this->handle, 2);
  }

  /** @return void */
  public function close() {
    if (!$this->handle) return;

    proc_close($this->handle);
    $this->handle= null;
  }

  /** @return void */
  public function __destruct() {
    $this->close();
  }
}