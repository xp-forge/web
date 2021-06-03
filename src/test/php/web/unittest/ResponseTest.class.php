<?php namespace web\unittest;

use io\Channel;
use io\streams\MemoryInputStream;
use lang\IllegalArgumentException;
use unittest\{Test, Expect, Values, TestCase};
use util\URI;
use web\io\{Buffered, TestOutput};
use web\{Cookie, Response};

class ResponseTest extends TestCase {

  /**
   * Assertion helper
   *
   * @param  string $expected
   * @param  web.Response $response
   * @throws unittest.AssertionFailedError
   */
  private function assertResponse($expected, $response) {
    $this->assertEquals($expected, $response->output()->bytes());
  }

  #[Test]
  public function can_create() {
    new Response(new TestOutput());
  }

  #[Test]
  public function status_initially_200() {
    $res= new Response(new TestOutput());
    $this->assertEquals(200, $res->status());
  }

  #[Test]
  public function status() {
    $res= new Response(new TestOutput());
    $res->answer(201);
    $this->assertEquals(201, $res->status());
  }

  #[Test]
  public function message() {
    $res= new Response(new TestOutput());
    $res->answer(201, 'Created');
    $this->assertEquals('Created', $res->message());
  }

  #[Test]
  public function custom_message() {
    $res= new Response(new TestOutput());
    $res->answer(201, 'Creation succeeded');
    $this->assertEquals('Creation succeeded', $res->message());
  }

  #[Test]
  public function headers_initially_empty() {
    $res= new Response(new TestOutput());
    $this->assertEquals([], $res->headers());
  }

  #[Test]
  public function header() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/plain');
    $this->assertEquals(['Content-Type' => 'text/plain'], $res->headers());
  }

  #[Test]
  public function headers() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/plain');
    $res->header('Content-Length', '0');
    $this->assertEquals(
      ['Content-Type' => 'text/plain', 'Content-Length' => '0'],
      $res->headers()
    );
  }

  #[Test]
  public function remove_header() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/plain');
    $res->header('Content-Type', null);
    $this->assertEquals([], $res->headers());
  }

  #[Test]
  public function multiple_header() {
    $res= new Response(new TestOutput());
    $res->header('Set-Cookie', ['theme=light', 'sessionToken=abc123']);
    $this->assertEquals(['Set-Cookie' => ['theme=light', 'sessionToken=abc123']], $res->headers());
  }

  #[Test]
  public function append_header() {
    $res= new Response(new TestOutput());
    $res->header('Set-Cookie', 'theme=light', true);
    $res->header('Set-Cookie', 'sessionToken=abc123', true);
    $this->assertEquals(['Set-Cookie' => ['theme=light', 'sessionToken=abc123']], $res->headers());
  }

  #[Test]
  public function uri_header() {
    $res= new Response(new TestOutput());
    $res->header('Location', new URI('http://example.com/'));
    $this->assertEquals(['Location' => 'http://example.com/'], $res->headers());
  }

  #[Test]
  public function cookie() {
    $res= new Response(new TestOutput());
    $res->cookie(new Cookie('theme', 'light'));
    $this->assertEquals('light', $res->cookies()[0]->value());
  }

  #[Test]
  public function cookies() {
    $cookies= [new Cookie('theme', 'Test'), (new Cookie('sessionToken', 'abc123'))->expires('Wed, 09 Jun 2021 10:18:14 GMT')];

    $res= new Response(new TestOutput());
    foreach ($cookies as $cookie) {
      $res->cookie($cookie);
    }

    $this->assertEquals($cookies, $res->cookies());
  }

  #[Test, Values([[200, 'OK', 'HTTP/1.1 200 OK'], [404, 'Not Found', 'HTTP/1.1 404 Not Found'], [200, null, 'HTTP/1.1 200 OK'], [404, null, 'HTTP/1.1 404 Not Found'], [200, 'Okay', 'HTTP/1.1 200 Okay'], [404, 'Nope', 'HTTP/1.1 404 Nope']])]
  public function answer($status, $message, $line) {
    $out= new TestOutput();

    $res= new Response($out);
    $res->answer($status, $message);
    $res->flush();

    $this->assertResponse($line."\r\n\r\n", $res);
  }

  #[Test]
  public function hint() {
    $res= new Response(new TestOutput());
    $res->hint(100, 'Continue');
    $res->answer(200, 'OK');
    $res->flush();

    $this->assertResponse("HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK\r\n\r\n", $res);
  }

  #[Test]
  public function hint_with_headers() {
    $res= new Response(new TestOutput());
    $res->hint(101, null, [
      'Upgrade'    => 'websocket',
      'Connection' => 'Upgrade'
    ]);

    $this->assertResponse(
      "HTTP/1.1 101 Switching Protocols\r\n".
      "Upgrade: websocket\r\n".
      "Connection: Upgrade\r\n".
      "\r\n",
      $res
    );
  }

  #[Test]
  public function hint_uses_and_retains_previously_set_headers() {
    $res= new Response(new TestOutput());
    $res->header('Link', ['</main.css>; rel=preload; as=style', '</script.js>; rel=preload; as=script']);
    $res->hint(103);
    $res->answer(200, 'OK');
    $res->flush();

    $this->assertResponse(
      "HTTP/1.1 103 Early Hints\r\n".
      "Link: </main.css>; rel=preload; as=style\r\n".
      "Link: </script.js>; rel=preload; as=script\r\n".
      "\r\n".
      "HTTP/1.1 200 OK\r\n".
      "Link: </main.css>; rel=preload; as=style\r\n".
      "Link: </script.js>; rel=preload; as=script\r\n".
      "\r\n",
      $res
    );
  }

  #[Test]
  public function send_headers() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/plain');
    $res->header('Content-Length', '0');
    $res->flush();

    $this->assertResponse("HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nContent-Length: 0\r\n\r\n", $res);
  }

  #[Test]
  public function send_html() {
    $res= new Response(new TestOutput());
    $res->send('<h1>Test</h1>', 'text/html');

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>",
      $res
    );
  }

  #[Test]
  public function transfer_stream_with_length() {
    $res= new Response(new TestOutput());
    $res->transfer(new MemoryInputStream('<h1>Test</h1>'), 'text/html', 13);

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>",
      $res
    );
  }

  #[Test]
  public function transfer_stream_chunked() {
    $res= new Response(new TestOutput());
    $res->transfer(new MemoryInputStream('<h1>Test</h1>'), 'text/html');

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nTransfer-Encoding: chunked\r\n\r\n".
      "d\r\n<h1>Test</h1>\r\n0\r\n\r\n",
      $res
    );
  }

  #[Test]
  public function transfer_stream_buffered() {
    $res= new Response((new TestOutput())->using(Buffered::class));
    $res->transfer(new MemoryInputStream('<h1>Test</h1>'), 'text/html');

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>",
      $res
    );
  }

  #[Test]
  public function transmit_stream_with_length() {
    $res= new Response(new TestOutput());
    foreach ($res->transmit(new MemoryInputStream('<h1>Test</h1>'), 'text/html', 13) as $_) { }

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>",
      $res
    );
  }

  #[Test]
  public function transmit_stream_chunked() {
    $res= new Response(new TestOutput());
    foreach ($res->transmit(new MemoryInputStream('<h1>Test</h1>'), 'text/html') as $_) { }

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nTransfer-Encoding: chunked\r\n\r\n".
      "d\r\n<h1>Test</h1>\r\n0\r\n\r\n",
      $res
    );
  }

  #[Test]
  public function transmit_stream_buffered() {
    $res= new Response((new TestOutput())->using(Buffered::class));
    foreach ($res->transmit(new MemoryInputStream('<h1>Test</h1>'), 'text/html') as $_) { }

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>",
      $res
    );
  }

  #[Test]
  public function transmit_channel() {
    $res= new Response(new TestOutput());
    $channel= new class() implements Channel {
      public function in() { return new MemoryInputStream('<h1>Test</h1>'); }
      public function out() { /* Not implemented */ }
    };
    foreach ($res->transmit($channel, 'text/html', 13) as $_) { }

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>",
      $res
    );
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function transmit_null() {
    $res= new Response(new TestOutput());
    foreach ($res->transmit(null) as $_) { }
  }

  #[Test]
  public function cookies_and_headers_are_merged() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/html');
    $res->cookie(new Cookie('toggle', 'future'));
    $res->flush();

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nSet-Cookie: toggle=future; SameSite=Lax; HttpOnly\r\n\r\n",
      $res
    );
  }
}