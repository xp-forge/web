<?php namespace xp\web\srv;

use Throwable as Any;
use lang\IllegalStateException;
use peer\Socket;
use util\Bytes;
use web\io\EventSource;
use websocket\Listener;
use websocket\protocol\Opcodes;

/**
 * Forwards websocket messages to an HTTP SSE endpoint.
 *
 * @test  web.unittest.server.ForwardMessagesTest
 * @see   https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events/Using_server-sent_events
 */
class ForwardMessages extends Listener {
  use Distribution;

  /**
   * Handles incoming message
   *
   * @param  websocket.protocol.Connection $conn
   * @param  string|util.Bytes $message
   * @return var
   */
  public function message($conn, $message) {
    $request= "POST {$conn->path()} HTTP/1.1\r\n";
    $headers= [
      'Sec-WebSocket-Version' => 9,
      'Sec-WebSocket-Id'      => $conn->id(),
      'Content-Type'          => $message instanceof Bytes ? 'application/octet-stream' : 'text/plain',
      'Content-Length'        => strlen($message),
    ];
    foreach ($headers + $conn->headers() as $name => $value) {
      $request.= "{$name}: {$value}\r\n";
    }

    // Wait briefly before retrying to find an available worker
    while (null === ($backend= $this->select())) {
      yield 'delay' => 1;
    }

    try {
      $backend->connect();
      $backend->write($request."\r\n".$message);

      $response= new Input($backend);
      foreach ($response->consume() as $_) { }
      if (200 !== $response->status()) {
        throw new IllegalStateException('Unexpected status code from backend://'.$conn->path().': '.$response->status());
      }

      // Process SSE stream
      foreach ($response->headers() as $_) { }
      foreach (new EventSource($response->incoming()) as $event => $data) {
        $value= strtr($data, ['\r' => "\r", '\n' => "\n"]);
        switch ($event) {
          case 'text': case null: $conn->send($value); break;
          case 'bytes': $conn->send(new Bytes($value)); break;
          case 'close': {
            sscanf($value, "%d:%[^\r]", $code, $reason);
            $conn->answer(Opcodes::CLOSE, pack('na*', $code, $reason));
            $conn->close();
            break;
          }
          default: throw new IllegalStateException('Unexpected event from backend://'.$conn->path().': '.$event);
        }
      }
    } catch (Any $e) {
      $conn->answer(Opcodes::CLOSE, pack('na*', 1011, $e->getMessage()));
      $conn->close();
    } finally {
      $backend->close();
    }
  }
}