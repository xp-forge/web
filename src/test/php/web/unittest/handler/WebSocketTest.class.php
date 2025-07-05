<?php namespace web\unittest\handler;

use test\{Assert, Test, Values};
use util\Bytes;
use web\handler\WebSocket;
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class WebSocketTest {

  /** Handles a request and returns the response generated from the handler */
  private function handle(Request $request, $origins= ['*']): Response {
    $response= new Response(new TestOutput());
    $echo= function($conn, $payload) {
      if ($payload instanceof Bytes) {
        $conn->send(new Bytes("\x08\x15{$payload}"));
      } else {
        $conn->send('Re: '.$payload);
      }
    };
    (new WebSocket($echo, $origins))->handle($request, $response)->next();
    return $response;
  }

  /** @return iterable */
  private function same() {

    // By default, enforces same-origin policy
    yield ['http://localhost:80', 101];
    yield ['http://localhost', 101];
    yield ['http://Localhost', 101];

    // Not allowed: Differing hosts, ports or scheme
    yield ['http://localhost:81', 403];
    yield ['http://example.localhost', 403];
    yield ['http://localhost.example.com', 403];
    yield ['https://localhost', 403];
  }

  /** @return iterable */
  private function origins() {

    // We allow all ports and schemes on localhost
    yield ['http://localhost', 101];
    yield ['https://localhost', 101];
    yield ['http://localhost:8080', 101];
    yield ['https://localhost:8443', 101];
    yield ['http://Localhost', 101];

    // Not allowed: localhost subdomains and unrelated domains
    yield ['http://example.localhost', 403];
    yield ['http://localhost.example.com', 403];
    yield ['http://evil.example.com', 403];
  }

  #[Test]
  public function can_create() {
    new WebSocket(function($conn, $payload) { });
  }

  #[Test]
  public function switching_protocols() {
    $response= $this->handle(new Request(new TestInput('GET', '/ws', [
      'Origin'                => 'http://localhost:8080',
      'Sec-WebSocket-Version' => 13,
      'Sec-WebSocket-Key'     => 'test',
    ])));
    Assert::equals(101, $response->status());
    Assert::equals('tNpbgC8ZQDOcSkHAWopKzQjJ1hI=', $response->headers()['Sec-WebSocket-Accept']);
  }

  #[Test]
  public function missing_origin() {
    $request= new Request(new TestInput('GET', '/ws', [
      'Sec-WebSocket-Version' => 13,
      'Sec-WebSocket-Key'     => 'test',
    ]));

    Assert::equals(403, $this->handle($request)->status());
  }

  #[Test, Values(from: 'same')]
  public function verify_same_origin($origin, $expected) {
    $request= new Request(new TestInput('GET', '/ws', [
      'Origin'                => $origin,
      'Host'                  => 'localhost:80',
      'Sec-WebSocket-Version' => 13,
      'Sec-WebSocket-Key'     => 'test',
    ]));

    Assert::equals($expected, $this->handle($request, [])->status());
  }

  #[Test, Values(from: 'origins')]
  public function verify_localhost_origin($origin, $expected) {
    $request= new Request(new TestInput('GET', '/ws', [
      'Origin'                => $origin,
      'Host'                  => 'localhost:8080',
      'Sec-WebSocket-Version' => 13,
      'Sec-WebSocket-Key'     => 'test',
    ]));

    Assert::equals($expected, $this->handle($request, ['*://localhost:*'])->status());
  }

  #[Test]
  public function translate_text_message() {
    $response= $this->handle(new Request(new TestInput('POST', '/ws', [
      'Origin'                => 'http://localhost:8080',
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
      'Origin'                => 'http://localhost:8080',
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