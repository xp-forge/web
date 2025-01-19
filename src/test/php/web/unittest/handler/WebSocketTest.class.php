<?php namespace web\unittest\handler;

use test\{Assert, Test};
use util\Bytes;
use web\handler\WebSocket;
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class WebSocketTest {

  /** Handles a request and returns the response generated from the handler */
  private function handle(Request $request): Response {
    $response= new Response(new TestOutput());
    $echo= function($conn, $payload) {
      if ($payload instanceof Bytes) {
        $conn->send(new Bytes("\x08\x15{$payload}"));
      } else {
        $conn->send('Re: '.$payload);
      }
    };
    (new WebSocket($echo))->handle($request, $response)->next();
    return $response;
  }

  #[Test]
  public function can_create() {
    new WebSocket(function($conn, $payload) { });
  }

  #[Test]
  public function switching_protocols() {
    $response= $this->handle(new Request(new TestInput('GET', '/ws', [
      'Sec-WebSocket-Version' => 13,
      'Sec-WebSocket-Key'     => 'test',
    ])));
    Assert::equals(101, $response->status());
    Assert::equals('tNpbgC8ZQDOcSkHAWopKzQjJ1hI=', $response->headers()['Sec-WebSocket-Accept']);
  }

  #[Test]
  public function translate_text_message() {
    $response= $this->handle(new Request(new TestInput('POST', '/ws', [
      'Sec-WebSocket-Version' => 9,
      'Sec-WebSocket-Id'      => 123,
      'Content-Type'          => 'text/plain',
      'Content-Length'        => 4,
    ], 'Test')));
    Assert::equals(200, $response->status());
    Assert::equals('text/event-stream', $response->headers()['Content-Type']);
    Assert::matches('/10\r\ndata: Re: Test\n/', $response->output()->bytes());
  }

  #[Test]
  public function translate_binary_message() {
    $response= $this->handle(new Request(new TestInput('POST', '/ws', [
      'Sec-WebSocket-Version' => 9,
      'Sec-WebSocket-Id'      => 123,
      'Content-Type'          => 'application/octet-stream',
      'Content-Length'        => 2,
    ], "\x47\x11")));
    Assert::equals(200, $response->status());
    Assert::equals('text/event-stream', $response->headers()['Content-Type']);
    Assert::matches('/19\r\nevent: bytes\ndata: .{4}\n/', $response->output()->bytes());
  }

  #[Test]
  public function non_websocket_request() {
    $response= $this->handle(new Request(new TestInput('GET', '/ws')));
    Assert::equals(426, $response->status());
  }

  #[Test]
  public function unsupported_websocket_version() {
    $response= $this->handle(new Request(new TestInput('GET', '/ws', ['Sec-WebSocket-Version' => 11])));
    Assert::equals(400, $response->status());
  }
}