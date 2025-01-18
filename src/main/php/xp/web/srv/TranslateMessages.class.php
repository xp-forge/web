<?php namespace xp\web\srv;

use Throwable as Any;
use lang\IllegalStateException;
use peer\Socket;
use util\Bytes;
use web\io\EventSource;
use websocket\Listener;
use websocket\protocol\Opcodes;

/**
 * Translates websocket messages into HTTP requests to an SSE endpoint
 *
 * @test  web.unittest.server.TranslateMessagesTest
 * @see   https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events/Using_server-sent_events
 */
class TranslateMessages extends Listener {
  private $backend;

  /** Creates a new instance */
  public function __construct(Socket $backend) {
    $this->backend= $backend;
  }

  /**
   * Handles incoming message
   *
   * @param  websocket.protocol.Connection $conn
   * @param  string|util.Bytes $message
   * @return var
   */
  public function message($conn, $message) {
    $type= $message instanceof Bytes ? 'application/octet-stream' : 'text/plain';
    $request= "POST {$conn->path()} HTTP/1.1\r\n";
    $headers= ['Sec-WebSocket-Version' => 9, 'Content-Type' => $type, 'Content-Length' => strlen($message)];
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
          case 'close': {
            sscanf($value, "%d:%[^\r]", $code, $reason);
            $conn->answer(Opcodes::CLOSE, pack('na*', $code, $reason));
            $conn->close();
            break;
          }
          default: throw new IllegalStateException('Unexpected event '.$event);
        }
      }
    } catch (Any $e) {
      $conn->answer(Opcodes::CLOSE, pack('na*', 1011, $e->getMessage()));
      $conn->close();
    } finally {
      $this->backend->close();
    }
  }
}