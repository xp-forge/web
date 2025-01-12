<?php namespace xp\web\srv;

use Throwable as Any;
use lang\Throwable;
use websocket\protocol\{Opcodes, Connection};

class WsProtocol extends Switchable {
  private $logging;
  private $connections= [];

  /**
   * Creates a new protocol instance
   *
   * @param  web.Logging $logging
   */
  public function __construct($logging) {
    $this->logging= $logging;
  }

  /**
   * Handle client switch
   *
   * @param  peer.Socket $socket
   * @param  var $context
   */
  public function handleSwitch($socket, $context) {
    $socket->setTimeout(600);
    $socket->useNoDelay();

    $id= spl_object_id($socket);
    $this->connections[$id]= new Connection(
      $socket,
      $id,
      $context['listener'],
      $context['request']->uri()->path(),
      $context['request']->headers()
    );
    $this->connections[$id]->open();
  }

  /**
   * Handle client data
   *
   * @param  peer.Socket $socket
   * @return void
   */
  public function handleData($socket) {
    $conn= $this->connections[spl_object_id($socket)];
    foreach ($conn->receive() as $type => $message) {
      try {
        if (Opcodes::CLOSE === $type) {
          $conn->close();
          $hint= 'status='.unpack('n', $message)[1];
        } else {
          yield from $conn->on($message) ?? [];
          $hint= '';
        }
      } catch (Any $e) {
        $hint= Throwable::wrap($e)->compoundMessage();
      }

      // TODO: Use logging facility
      // $this->logging->log($request, $response);
      \util\cmd\Console::writeLinef(
        "  \e[33m[%s %d %.3fkB]\e[0m WS %s %s%s",
        date('Y-m-d H:i:s'),
        getmypid(),
        memory_get_usage() / 1024,
        Opcodes::nameOf($type),
        $conn->path(),
        $hint ? " \e[2m[{$hint}]\e[0m" : ''
      );
    }
  }

  /**
   * Handle client disconnect
   *
   * @param  peer.Socket $socket
   */
  public function handleDisconnect($socket) {
    unset($this->connections[spl_object_id($socket)]);
  }

  /**
   * Handle I/O error
   *
   * @param  peer.Socket $socket
   * @param  lang.XPException $e
   */
  public function handleError($socket, $e) {
    unset($this->connections[spl_object_id($socket)]);
  }
}