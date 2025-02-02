<?php namespace xp\web\srv;

use Generator;
use peer\server\{AsyncServer, ServerProtocol};

/** Multiplex protocol */
class Protocol implements ServerProtocol {
  private $protocols= [];

  /** Creates a new instance of this multiplex protocol */
  public static function multiplex(): self {
    return new self();
  }

  /** Serves a given protocol */
  public function serving(string $protocol, Switchable $delegate): self {
    $this->protocols[$protocol]= $delegate;
    return $this;
  }

  /**
   * Initialize protocol
   *
   * @return bool
   */
  public function initialize() {
    foreach ($this->protocols as $protocol) {
      $protocol->initialize();
    }
    return true;
  }

  /**
   * Handle client connect
   *
   * @param  peer.Socket $socket
   * @return void
   */
  public function handleConnect($socket) {
    $this->protocols[spl_object_id($socket)]= current($this->protocols);
  }

  /**
   * Handle client data
   *
   * @param  peer.Socket $socket
   * @return iterable
   */
  public function handleData($socket) {
    $handle= spl_object_id($socket);
    $handler= $this->protocols[$handle]->handleData($socket);

    if ($handler instanceof Generator) {
      yield from $handler;

      if ($switch= $handler->getReturn()) {
        list($protocol, $context)= $switch;

        $this->protocols[$handle]= $this->protocols[$protocol];
        $this->protocols[$handle]->handleSwitch($socket, $context);
      }
    }
  }

  /**
   * Handle client disconnect
   *
   * @param  peer.Socket $socket
   * @return void
   */
  public function handleDisconnect($socket) {
    $handle= spl_object_id($socket);
    if (isset($this->protocols[$handle])) {
      $this->protocols[$handle]->handleDisconnect($socket);
      unset($this->protocols[$handle]);
    }
    $socket->close();
  }

  /**
   * Handle I/O error
   *
   * @param  peer.Socket $socket
   * @param  lang.XPException $e
   * @return void
   */
  public function handleError($socket, $e) {
    $handle= spl_object_id($socket);
    if (isset($this->protocols[$handle])) {
      $this->protocols[$handle]->handleError($socket, $e);
      unset($this->protocols[$handle]);
    }
    $socket->close();
  }
}