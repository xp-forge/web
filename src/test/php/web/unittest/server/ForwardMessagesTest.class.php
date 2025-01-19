<?php namespace web\unittest\server;

use test\{Assert, Test, Values};
use util\Bytes;
use web\unittest\Channel;
use websocket\protocol\Connection;
use xp\web\srv\ForwardMessages;

class ForwardMessagesTest {
  const WSID= 6100;

  /** Creates a HTTP message */
  private function message(...$lines): string {
    return implode("\r\n", $lines);
  }

  #[Test]
  public function can_create() {
    new ForwardMessages(new Channel([]));
  }

  #[Test, Values(["d\r\ndata: Tested\n\r\n0\r\n\r\n", "19\r\nevent: text\ndata: Tested\n\r\n0\r\n\r\n"])]
  public function text($payload) {
    $request= $this->message(
      'POST /ws HTTP/1.1',
      'Sec-WebSocket-Version: 9',
      'Sec-WebSocket-Id: '.self::WSID,
      'Content-Type: text/plain',
      'Content-Length: 4',
      '',
      'Test',
    );
    $response= $this->message(
      'HTTP/1.1 200 OK',
      'Content-Type: text/event-stream',
      'Transfer-Encoding: chunked',
      '',
      $payload
    );

    $backend= new Channel([$response]);
    $ws= new Channel([]);
    $fixture= new ForwardMessages($backend);
    $fixture->message(new Connection($ws, self::WSID, null, '/ws', []), 'Test');
    
    Assert::equals($request, implode('', $backend->out));
    Assert::equals("\201\006Tested", implode('', $ws->out));
    Assert::true($ws->isConnected());
  }

  #[Test]
  public function binary() {
    $request= $this->message(
      'POST /ws HTTP/1.1',
      'Sec-WebSocket-Version: 9',
      'Sec-WebSocket-Id: '.self::WSID,
      'Content-Type: application/octet-stream',
      'Content-Length: 2',
      '',
      "\010\017",
    );
    $response= $this->message(
      'HTTP/1.1 200 OK',
      'Content-Type: text/event-stream',
      'Transfer-Encoding: chunked',
      '',
      "15\r\nevent: bytes\ndata: \047\011\n\r\n0\r\n\r\n"
    );

    $backend= new Channel([$response]);
    $ws= new Channel([]);
    $fixture= new ForwardMessages($backend);
    $fixture->message(new Connection($ws, self::WSID, null, '/ws', []), new Bytes([8, 15]));
    
    Assert::equals($request, implode('', $backend->out));
    Assert::equals("\202\002\047\011", implode('', $ws->out));
    Assert::true($ws->isConnected());
  }

  #[Test]
  public function close() {
    $request= $this->message(
      'POST /ws HTTP/1.1',
      'Sec-WebSocket-Version: 9',
      'Sec-WebSocket-Id: '.self::WSID,
      'Content-Type: application/octet-stream',
      'Content-Length: 2',
      '',
      "\010\017",
    );
    $response= $this->message(
      'HTTP/1.1 200 OK',
      'Content-Type: text/event-stream',
      'Transfer-Encoding: chunked',
      '',
      "1d\r\nevent: close\ndata: 1011:Error\r\n0\r\n\r\n"
    );

    $backend= new Channel([$response]);
    $ws= new Channel([]);
    $fixture= new ForwardMessages($backend);
    $fixture->message(new Connection($ws, self::WSID, null, '/ws', []), new Bytes([8, 15]));

    Assert::equals($request, implode('', $backend->out));
    Assert::equals("\210\007\003\363Error", implode('', $ws->out));
    Assert::false($ws->isConnected());
  }

  #[Test]
  public function unexpected_type() {
    $request= $this->message(
      'POST /ws HTTP/1.1',
      'Sec-WebSocket-Version: 9',
      'Sec-WebSocket-Id: '.self::WSID,
      'Content-Type: text/plain',
      'Content-Length: 4',
      '',
      'Test',
    );
    $response= $this->message(
      'HTTP/1.1 200 OK',
      'Content-Type: text/event-stream',
      'Transfer-Encoding: chunked',
      '',
      "16\r\nevent: unknown\ndata: \n\r\n0\r\n\r\n"
    );

    $backend= new Channel([$response]);
    $ws= new Channel([]);
    $fixture= new ForwardMessages($backend);
    $fixture->message(new Connection($ws, self::WSID, null, '/ws', []), 'Test');

    Assert::equals($request, implode('', $backend->out));
    Assert::equals("\210\056\003\363Unexpected event from backend:///ws: unknown", implode('', $ws->out));
    Assert::false($ws->isConnected());
  }

  #[Test]
  public function backend_error() {
    $request= $this->message(
      'POST /ws HTTP/1.1',
      'Sec-WebSocket-Version: 9',
      'Sec-WebSocket-Id: '.self::WSID,
      'Content-Type: text/plain',
      'Content-Length: 4',
      '',
      'Test',
    );
    $response= $this->message(
      'HTTP/1.1 500 Internal Server Errror',
      'Content-Type: text/plain',
      'Content-Length: 7',
      '',
      'Testing'
    );

    $backend= new Channel([$response]);
    $ws= new Channel([]);
    $fixture= new ForwardMessages($backend);
    $fixture->message(new Connection($ws, self::WSID, null, '/ws', []), 'Test');

    Assert::equals($request, implode('', $backend->out));
    Assert::equals("\210\060\003\363Unexpected status code from backend:///ws: 500", implode('', $ws->out));
    Assert::false($ws->isConnected());
  }
}