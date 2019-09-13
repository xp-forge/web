<?php namespace web\protocol;

use peer\server\ServerProtocol;
use web\Request;
use web\Response;

class Protocols implements ServerProtocol {
  private $http, $upgrade;
  private $proto= [];

  /**
   * Creates a new protocols instance
   *
   * @param  web.protocol.Http $http
   * @param  [:web.protocol.Protocol] $upgrade
   */
  public function __construct(Http $http, array $upgrade) {
    $this->http= $http;
    $this->upgrade= $upgrade;
  }

  /** @return bool */
  public function initialize() { return true; }

  /**
   * Handle client connect
   *
   * @param  peer.Socket $socket
   * @return void
   */
  public function handleConnect($socket) {
    // NOOP
  }

  /**
   * Handle client disconnect
   *
   * @param  peer.Socket $socket
   * @return void
   */
  public function handleDisconnect($socket) {
    $lookup= (int)$socket->getHandle();
    if (isset($this->proto[$lookup])) {
      $this->proto[$lookup]->handleDisconnect($socket);
      unset($this->proto[$lookup]);
    }
  }

  /**
   * Handle client data
   *
   * @param  peer.Socket $socket
   * @return void
   */
  public function handleData($socket) {
    $lookup= (int)$socket->getHandle();
    if (isset($this->proto[$lookup])) {
      $this->proto[$lookup]->handleData($socket);
      return;
    }

    // Determine protocol based on the first request from this connection
    $input= new SocketInput($socket);
    if ($version= $input->version()) {
      $request= new Request($input);
      $response= new Response(new SocketOutput($socket, $version));

      if ($upgrade= $request->header('Upgrade')) {
        if (isset($this->upgrade[$upgrade])) {
          $continue= $this->upgrade[$upgrade]->next($version, $request, $response) ? $this->upgrade[$upgrade] : null;
        } else {
          $response->answer(400);
          $response->header('Connection', 'close');
          $response->send('Cannot upgrade to '.$upgrade.' protocol', 'text/plain');
          $continue= null;
        }
      } else {
        $continue= $this->http->next($version, $request, $response) ? $this->http : null;
      }

      // Keep the socket open, remember protocol
      if ($continue) {
        $this->proto[$lookup]= $continue;
        return;
      }
    } else if (SocketInput::CLOSE !== $input->kind) {
      $this->http->incomplete($socket, $input->kind);
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
    $lookup= (int)$socket->getHandle();
    if (isset($this->proto[$lookup])) {
      $this->proto[$lookup]->handleError($socket, $e);
      unset($this->proto[$lookup]);
    }
  }
}