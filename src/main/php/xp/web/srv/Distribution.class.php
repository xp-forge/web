<?php namespace xp\web\srv;

use peer\Socket;

/** Selects worker targets for distributing requests */
trait Distribution {
  private $workers;

  /** @param xp.web.srv.Worker[] $workers */
  public function __construct(array $workers) {
    $this->workers= $workers;
  }

  /** Returns first available idle worker socket */
  private function select(): ?Socket {
    foreach ($this->workers as $worker) {
      if (!$worker->socket->isConnected()) return $worker->socket;
    }
    return null;
  }
}