<?php namespace xp\web\srv;

use peer\Socket;

/** Forwards HTTP requests to the given backend */
class ForwardRequests extends Switchable {
  private $backend;

  /** Creates a new instance */
  public function __construct(Socket $backend) {
    $this->backend= $backend;
  }

  /**
   * Handle client data
   *
   * @param  peer.Socket $socket
   * @return void
   */
  public function handleData($socket) {
    static $exclude= ['Remote-Addr' => true];

    $request= new Input($socket);
    yield from $request->consume();

    if (Input::REQUEST === $request->kind) {
      $this->backend->connect();
      $message= "{$request->method()} {$request->uri()} HTTP/{$request->version()}\r\n";
      $headers= [];
      foreach ($request->headers() as $name => $value) {
        isset($exclude[$name]) || $message.= "{$name}: {$value}\r\n";
        $headers[$name]= $value;
      }
      // \util\cmd\Console::writeLine('>>> ', $message);
      $this->backend->write($message."\r\n");

      if ($stream= $request->incoming()) {
        while ($stream->available()) {
          $this->backend->write($stream->read());
        }
      }
      yield 'write' => $this->socket;

      $response= new Input($this->backend);
      foreach ($response->consume() as $_) { }

      // Switch protocols
      if (101 === $response->status()) {
        $result= ['websocket', ['uri' => $request->uri(), 'headers' => $headers]];
      } else {
        $result= null;
      }

      $message= "HTTP/{$response->version()} {$response->status()} {$response->message()}\r\n";
      foreach ($response->headers() as $name => $value) {
        isset($exclude[$name]) || $message.= "{$name}: {$value}\r\n";
      }
      // \util\cmd\Console::writeLine('<<< ', $message);
      $socket->write($message."\r\n");

      if ($stream= $response->incoming()) {
        while ($stream->available()) {
          $socket->write($stream->read());
        }
      }
      $this->backend->close();

      return $result;
    } else if (Input::CLOSE === $request->kind) {
      $socket->close();
    } else {
      // \util\cmd\Console::writeLine('!!! ', $request);
      $socket->close();
    }
  }
}