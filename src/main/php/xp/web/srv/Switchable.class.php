<?php namespace xp\web\srv;

use peer\server\ServerProtocol;

abstract class Switchable implements ServerProtocol {

  /**
   * Initialize Protocol
   *
   * @return bool
   */
  public function initialize() { return true; }

  /**
   * Handle client connect
   *
   * @param  peer.Socket $socket
   */
  public function handleConnect($socket) {
    $this->handleSwitch($socket, null);
  }

  /**
   * Handle client switch
   *
   * @param  peer.Socket $socket
   * @param  var $context
   */
  public function handleSwitch($socket, $context) {
    // NOOP
  }

  /**
   * Handle client disconnect
   *
   * @param  peer.Socket $socket
   */
  public function handleDisconnect($socket) {
    // NOOP
  }

  /**
   * Handle I/O error
   *
   * @param  peer.Socket $socket
   * @param  lang.XPException $e
   */
  public function handleError($socket, $e) {
    // NOOP
  }
}