<?php namespace xp\web\srv;

use peer\Socket;
use web\io\{ReadChunks, ReadLength};

/**
 * Forwards HTTP requests to the given backend
 *
 * @test  web.unittest.server.ForwardRequestsTest
 */
class ForwardRequests extends Switchable {
  private $backend;

  /** Creates a new instance */
  public function __construct(Worker $worker) {
    $this->backend= $worker->socket;
  }

  /**
   * Transmits data from an optional stream to a given target socket,
   * handling chunked transfer-encoding.
   *
   * @see    https://developer.mozilla.org/de/docs/Web/HTTP/Headers/Transfer-Encoding
   * @param  io.streams.InputStream $stream
   * @param  peer.Socket $target
   * @return iterable
   */
  private function transmit($stream, $target) {
    if (null === $stream) {
      // NOOP
    } else if ($stream instanceof ReadChunks) {
      while ($stream->available()) {
        yield;
        $chunk= $stream->read();
        $target->write(dechex(strlen($chunk))."\r\n".$chunk."\r\n");
      }
      $target->write("0\r\n\r\n");
    } else {
      while ($stream->available()) {
        yield;
        $target->write($stream->read());
      }
    }
  }

  /**
   * Handle client data
   *
   * @param  peer.Socket $socket
   * @return iterable
   */
  public function handleData($socket) {
    static $exclude= ['Remote-Addr' => true];

    $request= new Input($socket);
    yield from $request->consume();

    if (Input::REQUEST === $request->kind) {
      $this->backend->connect();
      try {
        $message= "{$request->method()} {$request->resource()} HTTP/{$request->version()}\r\n";
        $headers= [];
        foreach ($request->headers() as $name => $value) {
          isset($exclude[$name]) || $message.= "{$name}: {$value}\r\n";
          $headers[$name]= $value;
        }
        // \util\cmd\Console::writeLine('>>> ', $message);
        $this->backend->write($message."\r\n");
        foreach ($this->transmit($request->incoming(), $this->backend) as $step) {
          // yield 'read' => $socket;
        }

        $response= new Input($this->backend);
        foreach ($response->consume() as $_) { }

        // Switch protocols
        if (101 === $response->status()) {
          $result= ['websocket', ['path' => $request->resource(), 'headers' => $headers]];
        } else {
          $result= null;
        }

        // yield 'write' => $socket;
        $message= "HTTP/{$response->version()} {$response->status()} {$response->message()}\r\n";
        foreach ($response->headers() as $name => $value) {
          isset($exclude[$name]) || $message.= "{$name}: {$value}\r\n";
        }
        // \util\cmd\Console::writeLine('<<< ', $message);
        $socket->write($message."\r\n");

        foreach ($this->transmit($response->incoming(), $socket) as $step) {
          // yield 'write' => $socket;
        }
      } finally {
        $this->backend->close();
      }

      return $result;
    } else if (Input::CLOSE === $request->kind) {
      $socket->close();
    } else {
      // \util\cmd\Console::writeLine('!!! ', $request);
      $socket->close();
    }
  }
}