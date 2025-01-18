<?php namespace web\io;

use io\streams\Streams;
use lang\IllegalStateException;
use util\Bytes;
use websocket\protocol\{Opcodes, Connection};

class EventSink extends Connection {
  private $request, $out;

  public function __construct($request, $response) {
    $this->request= $request;
    $this->out= $response->stream();

    $uri= $request->uri()->path();
    if ($query= $request->uri()->query()) {
      $uri.= '?'.$query;
    }

    parent::__construct(null, null, null, $uri, $request->headers());
  }

  public function receive() {
    switch ($mime= $this->request->header('Content-Type')) {
      case 'text/plain': yield Opcodes::TEXT => Streams::readAll($this->request->stream()); break;
      case 'application/octet-stream': yield Opcodes::BINARY => new Bytes(Streams::readAll($this->request->stream())); break;
      default: throw new IllegalStateException('Unexpected content type '.$mime);
    }
  }

  public function send($message) {
    if ($message instanceof Bytes) {
      $this->out->write("event: bytes\ndata: ".addcslashes($message, "\r\n")."\n\n");
    } else {
      $this->out->write("data: ".addcslashes($message, "\r\n")."\n\n");
    }
  }

  public function close($code= 1000, $reason= '') {
    $this->out->write("event: close\ndata: ".$code.':'.addcslashes($reason, "\r\n")."\n\n");
  }

  public function flush() {
    $this->out->finish();
  }
}