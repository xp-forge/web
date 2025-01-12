<?php namespace xp\web\srv;

use Generator;
use peer\server\{AsyncServer, ServerProtocol};

/** Multiplex protocol */
class Protocol implements ServerProtocol {
  private $protocols= [];

  static function __static() {

    // PHP 7.0 and 7.1 compatibility
    if (!function_exists('spl_object_id')) {
      function spl_object_id($object) { return spl_object_hash($object); }
    }
  }

  /** Creates a new instance of this multiplex protocol */
  public static function multiplex(): self {

    // Compatibility with older xp-framework/networking libraries, see issue #79
    // Unwind generators returned from handleData() to guarantee their complete
    // execution.
    if (class_exists(AsyncServer::class, true)) {
      return new self();
    } else {
      return new class() extends Protocol {
        public function handleData($socket) {
          foreach (parent::handleData($socket) as $_) { }
        }
      };
    }
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
   */
  public function handleConnect($socket) {
    $this->protocols[spl_object_id($socket)]= current($this->protocols);
  }

  /**
   * Handle client data
   *
   * @param  peer.Socket $socket
   * @return void
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
   */
  public function handleDisconnect($socket) {
    $handle= spl_object_id($socket);
    $this->protocols[$handle]->handleDisconnect($socket);

    unset($this->protocols[$handle]);
    $socket->close();
  }

  /**
   * Handle I/O error
   *
   * @param  peer.Socket $socket
   * @param  lang.XPException $e
   */
  public function handleError($socket, $e) {
    $handle= spl_object_id($socket);
    $this->protocols[$handle]->handleError($socket, $e);

    unset($this->protocols[$handle]);
    $socket->close();
  }
}