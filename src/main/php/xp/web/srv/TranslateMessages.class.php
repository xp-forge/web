<?php namespace xp\web\srv;

use Throwable as Any;
use lang\IllegalStateException;
use peer\Socket;
use util\Bytes;
use web\io\EventSource;
use websocket\protocol\{Opcodes, Connection};

/**
 * Translates websocket messages into HTTP requests to an SSE endpoint
 *
 * @see  https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events/Using_server-sent_events
 */
class TranslateMessages extends Switchable {
  private $connections= [];
  private $backend;

  /** Creates a new instance */
  public function __construct(Socket $backend) {
    $this->backend= $backend;
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
      null,
      $context['uri'],
      $context['headers']
    );
    $this->connections[$id]->open();
  }

  private function decode($data) {
    return strtr($data, ['\r' => "\r", '\n' => "\n"]);
  }

  /**
   * Handle client data
   *
   * @param  peer.Socket $socket
   * @return void
   */
  public function handleData($socket) {
    static $mime= [Opcodes::TEXT => 'text/plain', Opcodes::BINARY => 'application/octet-stream'];

    $conn= $this->connections[spl_object_id($socket)];
    foreach ($conn->receive() as $type => $message) {
      if (Opcodes::CLOSE === $type) {
        $conn->close();
      } else {
        $request= "POST {$conn->path()} HTTP/1.1\r\n";
        $headers= ['Sec-WebSocket-Version' => 9, 'Content-Type' => $mime[$type], 'Content-Length' => strlen($message)];
        foreach ($headers + $conn->headers() as $name => $value) {
          $request.= "{$name}: {$value}\r\n";
        }

        try {
          $this->backend->connect();
          $this->backend->write($request."\r\n".$message);

          $response= new Input($this->backend);
          foreach ($response->consume() as $_) { }
          if (200 !== $response->status()) {
            throw new IllegalStateException('Unexpected status code from backend://'.$conn->path().': '.$response->status());
          }

          // Process SSE stream
          foreach ($response->headers() as $_) { }
          foreach (new EventSource($response->incoming()) as $event => $data) {
            $value= strtr($data, ['\r' => "\r", '\n' => "\n"]);
            switch ($event) {
              case null: case 'text': $conn->send($value); break;
              case 'bytes': $conn->send(new Bytes($value)); break;
              case 'close': $conn->close(...explode(':', $value)); break;
              default: throw new IllegalStateException('Unexpected event '.$event);
            }
          }
        } catch (Any $e) {
          $conn->close(1011, $e->getMessage());
        } finally {
          $this->backend->close();
        }
      }
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