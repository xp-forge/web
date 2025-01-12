<?php namespace web\unittest\handler;

use test\{Assert, Test};
use web\handler\WebSocket;
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class WebSocketTest {

  /** Handles a request and returns the response generated from the handler */
  private function handle(Request $request): Response {
    $response= new Response(new TestOutput());
    (new WebSocket(function($conn, $payload) { }))->handle($request, $response)->next();
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