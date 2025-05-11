<?php namespace web\unittest\io;

use test\{Assert, Test};
use util\Bytes;
use web\io\{EventSink, TestInput, TestOutput};
use web\{Request, Response};
use websocket\protocol\Opcodes;

class EventSinkTest {

  #[Test]
  public function can_create() {
    new EventSink(new Request(new TestInput('POST', '/ws')), new Response(new TestOutput()));
  }

  #[Test]
  public function receive_text_message() {
    $request= new Request(new TestInput('POST', '/ws', [
      'Sec-WebSocket-Version' => 9,
      'Sec-WebSocket-Id'      => 123,
      'Content-Type'          => 'text/plain',
      'Content-Length'        => 4,
    ], 'Test'));

    Assert::equals(
      [Opcodes::TEXT => 'Test'],
      iterator_to_array((new EventSink($request, new Response(new TestOutput())))->receive())
    );
  }

  #[Test]
  public function receive_binary_message() {
    $request= new Request(new TestInput('POST', '/ws', [
      'Sec-WebSocket-Version' => 9,
      'Sec-WebSocket-Id'      => 123,
      'Content-Type'          => 'application/octet-stream',
      'Content-Length'        => 2,
    ], "\x47\x11"));

    Assert::equals(
      [Opcodes::BINARY => new Bytes("\x47\x11")],
      iterator_to_array((new EventSink($request, new Response(new TestOutput())))->receive())
    );
  }

  #[Test]
  public function send_text_message() {
    $response= new Response(new TestOutput());
    (new EventSink(new Request(new TestInput('POST', '/ws')), $response))->send('Test');

    Assert::matches('/data: Test\n/', $response->output()->bytes());
  }

  #[Test]
  public function send_binary_message() {
    $response= new Response(new TestOutput());
    (new EventSink(new Request(new TestInput('POST', '/ws')), $response))->send(new Bytes("\x47\x11"));

    Assert::matches('/event: bytes\ndata: .{2}\n/', $response->output()->bytes());
  }

  #[Test]
  public function close_message() {
    $response= new Response(new TestOutput());
    (new EventSink(new Request(new TestInput('POST', '/ws')), $response))->close();

    Assert::matches('/event: close\ndata: 1000:\n/', $response->output()->bytes());
  }

  #[Test]
  public function close_message_with_reason() {
    $response= new Response(new TestOutput());
    (new EventSink(new Request(new TestInput('POST', '/ws')), $response))->close(1011, 'Test');

    Assert::matches('/event: close\ndata: 1011:Test\n/', $response->output()->bytes());
  }
}