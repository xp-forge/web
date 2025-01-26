<?php namespace web\io;

use io\streams\Streams;
use lang\IllegalStateException;
use util\Bytes;
use websocket\protocol\{Opcodes, Connection};

/** @test web.unittest.io.EventSinkTest */
class EventSink extends Connection {
  private $request, $out;

  /**
   * Creates a new event sink
   *
   * @param  web.Request $request
   * @param  web.Response $response
   */
  public function __construct($request, $response) {
    $this->request= $request;
    $this->out= $response->stream();
    parent::__construct(null, null, null, $request->uri()->resource(), $request->headers());
  }

  /**
   * Receives messages
   *
   * @return iterable
   */
  public function receive() {
    switch ($mime= $this->request->header('Content-Type')) {
      case 'text/plain': yield Opcodes::TEXT => Streams::readAll($this->request->stream()); break;
      case 'application/octet-stream': yield Opcodes::BINARY => new Bytes(Streams::readAll($this->request->stream())); break;
      default: throw new IllegalStateException('Unexpected content type '.$mime);
    }
  }

  /**
   * Sends a websocket message
   *
   * @param  string|util.Bytes $message
   * @return void
   */
  public function send($message) {
    if ($message instanceof Bytes) {
      $this->out->write("event: bytes\ndata: ".addcslashes($message, "\r\n")."\n\n");
    } else {
      $this->out->write("data: ".addcslashes($message, "\r\n")."\n\n");
    }
  }

  /**
   * Closes the websocket connection
   *
   * @param  int $code
   * @param  string $reason
   * @return void
   */
  public function close($code= 1000, $reason= '') {
    $this->out->write("event: close\ndata: ".$code.':'.addcslashes($reason, "\r\n")."\n\n");
  }
}