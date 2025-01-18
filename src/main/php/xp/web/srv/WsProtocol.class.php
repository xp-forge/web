<?php namespace xp\web\srv;

use Throwable as Any;
use lang\{Throwable, FormatException};
use util\Bytes;
use websocket\protocol\{Opcodes, Connection};

class WsProtocol extends Switchable {
  private $logging, $listener;
  private $connections= [];

  /**
   * Creates a new protocol instance
   *
   * @param  web.Logging $logging
   * @param  ?websocket.Listener $listener
   */
  public function __construct($logging, $listener= null) {
    $this->logging= $logging;
    $this->listener= $listener;
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
      $context['listener'] ?? $this->listener,
      $context['path'],
      $context['headers']
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
    foreach ($conn->receive() as $opcode => $payload) {
      try {
        switch ($opcode) {
          case Opcodes::TEXT:
            if (!preg_match('//u', $payload)) {
              $conn->answer(Opcodes::CLOSE, pack('n', 1007));
              $hints= ['error' => new FormatException('Malformed payload')];
              $socket->close();
              break;
            }

            yield from $conn->on($payload) ?? [];
            $hints= [];
            break;

          case Opcodes::BINARY:
            yield from $conn->on(new Bytes($payload)) ?? [];
            $hints= [];
            break;

          case Opcodes::PING:  // Answer a PING frame with a PONG
            $conn->answer(Opcodes::PONG, $payload);
            $hints= [];
            break;

          case Opcodes::PONG:  // Do not answer PONGs
            $hints= [];
            break;

          case Opcodes::CLOSE: // Close connection
            if ('' === $payload) {
              $close= ['code' => 1000, 'reason' => ''];
            } else {
              $close= unpack('ncode/a*reason', $payload);
              if (!preg_match('//u', $close['reason'])) {
                $close= ['code' => 1007, 'reason' => ''];
              } else if ($close['code'] > 2999 || in_array($close['code'], [1000, 1001, 1002, 1003, 1007, 1008, 1009, 1010, 1011])) {
                // Answer with client code and reason
              } else {
                $close= ['code' => 1002, 'reason' => ''];
              }
            }

            $conn->answer(Opcodes::CLOSE, pack('na*', $close['code'], $close['reason']));
            $conn->close();
            $hints= $close;
            break;
        }
      } catch (Any $e) {
        $hints= ['error' => Throwable::wrap($e)];
      }

      $this->logging->log('WS', Opcodes::nameOf($opcode), $conn->path(), $hints);
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