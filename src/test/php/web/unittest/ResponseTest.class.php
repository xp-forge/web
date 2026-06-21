<?php namespace web\unittest;

use io\Channel;
use io\streams\MemoryInputStream;
use lang\{IllegalArgumentException, IllegalStateException};
use test\{Assert, Expect, Test, Values};
use util\URI;
use web\io\{Buffered, TestOutput};
use web\{Cookie, Response, Parameterized};

class ResponseTest {

  /** @return iterable */
  private function answers() {

    // Supplied as-is
    yield [200, 'OK', 'HTTP/1.1 200 OK'];
    yield [404, 'Not Found', 'HTTP/1.1 404 Not Found'];

    // Status message derived from code
    yield [200, null, 'HTTP/1.1 200 OK'];
    yield [404, null, 'HTTP/1.1 404 Not Found'];

    // Status message overridden
    yield [200, 'Okay', 'HTTP/1.1 200 Okay'];
    yield [404, 'Nope', 'HTTP/1.1 404 Nope'];
  }

  /** @return iterable */
  private function filenames() {

    // Simple file names
    yield ['test.txt', 'filename=test.txt'];
    yield ['a b.txt', 'filename="a b.txt"'];
    yield ['"hello".txt', 'filename="\"hello\".txt"'];
    yield ['über.txt', "filename*=UTF-8''%C3%BCber.txt"];

    // In the form of [name => lang]
    yield [['test.txt' => null], "filename*=UTF-8''test.txt"];
    yield [['über.txt' => null], "filename*=UTF-8''%C3%BCber.txt"];
    yield [['über.txt' => 'de'], "filename*=UTF-8'de'%C3%BCber.txt"];

    // As received from Parameterized::params()
    yield [['lang' => 'de', 'value' => 'über.txt'], "filename*=UTF-8'de'%C3%BCber.txt"];
  }

  #[Test]
  public function can_create() {
    new Response(new TestOutput());
  }

  #[Test]
  public function status_initially_200() {
    $res= new Response(new TestOutput());
    Assert::equals(200, $res->status());
  }

  #[Test]
  public function status() {
    $res= new Response(new TestOutput());
    $res->answer(201);
    Assert::equals(201, $res->status());
  }

  #[Test]
  public function message() {
    $res= new Response(new TestOutput());
    $res->answer(201, 'Created');
    Assert::equals('Created', $res->message());
  }

  #[Test]
  public function custom_message() {
    $res= new Response(new TestOutput());
    $res->answer(201, 'Creation succeeded');
    Assert::equals('Creation succeeded', $res->message());
  }

  #[Test]
  public function headers_initially_empty() {
    $res= new Response(new TestOutput());
    Assert::equals([], $res->headers());
  }

  #[Test]
  public function header() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/plain');
    Assert::equals(['Content-Type' => 'text/plain'], $res->headers());
  }

  #[Test]
  public function headers() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/plain');
    $res->header('Content-Length', '0');
    Assert::equals(['Content-Type' => 'text/plain', 'Content-Length' => '0'], $res->headers());
  }

  #[Test]
  public function remove_header() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/plain');
    $res->header('Content-Type', null);
    Assert::equals([], $res->headers());
  }

  #[Test]
  public function multiple_header() {
    $res= new Response(new TestOutput());
    $res->header('Set-Cookie', ['theme=light', 'sessionToken=abc123']);
    Assert::equals(['Set-Cookie' => ['theme=light', 'sessionToken=abc123']], $res->headers());
  }

  #[Test]
  public function append_header() {
    $res= new Response(new TestOutput());
    $res->header('Set-Cookie', 'theme=light', true);
    $res->header('Set-Cookie', 'sessionToken=abc123', true);
    Assert::equals(['Set-Cookie' => ['theme=light', 'sessionToken=abc123']], $res->headers());
  }

  #[Test]
  public function uri_header() {
    $res= new Response(new TestOutput());
    $res->header('Location', new URI('http://example.com/'));
    Assert::equals(['Location' => 'http://example.com/'], $res->headers());
  }

  #[Test]
  public function cookie() {
    $res= new Response(new TestOutput());
    $res->cookie(new Cookie('theme', 'light'));
    Assert::equals('light', $res->cookies()[0]->value());
  }

  #[Test]
  public function overwrite_cookie() {
    $res= new Response(new TestOutput());
    $res->cookie(new Cookie('theme', 'light'));
    $res->cookie(new Cookie('theme', 'dark'));
    Assert::equals(['dark'], array_map(function($c) { return $c->value(); }, $res->cookies()));
  }

  #[Test]
  public function append_cookie() {
    $res= new Response(new TestOutput());
    $res->cookie(new Cookie('theme', 'light'));
    $res->cookie(new Cookie('theme', 'dark'), true);
    Assert::equals(['light', 'dark'], array_map(function($c) { return $c->value(); }, $res->cookies()));
  }

  #[Test]
  public function cookies() {
    $cookies= [new Cookie('theme', 'Test'), (new Cookie('sessionToken', 'abc123'))->expires('Wed, 09 Jun 2021 10:18:14 GMT')];

    $res= new Response(new TestOutput());
    foreach ($cookies as $cookie) {
      $res->cookie($cookie);
    }

    Assert::equals($cookies, $res->cookies());
  }

  #[Test, Values(from: 'answers')]
  public function answer($status, $message, $line) {
    $out= new TestOutput();

    $res= new Response($out);
    $res->answer($status, $message);
    $res->flush();

    Assert::that($res->output()->bytes())->isEqualTo($line."\r\n\r\n");
  }

  #[Test]
  public function trace() {
    $res= new Response(new TestOutput());
    $res->trace('request-time-ms', 1);
    Assert::equals(['request-time-ms' => 1], $res->trace);
  }

  #[Test]
  public function hint() {
    $res= new Response(new TestOutput());
    $res->hint(100, 'Continue');
    $res->answer(200, 'OK');
    $res->flush();

    Assert::that($res->output()->bytes())->isEqualTo("HTTP/1.1 100 Continue\r\n\r\nHTTP/1.1 200 OK\r\n\r\n");
  }

  #[Test]
  public function hint_with_headers() {
    $res= new Response(new TestOutput());
    $res->hint(101, null, [
      'Upgrade'    => 'websocket',
      'Connection' => 'Upgrade'
    ]);

    Assert::that($res->output()->bytes())->isEqualTo(
      "HTTP/1.1 101 Switching Protocols\r\n".
      "Upgrade: websocket\r\n".
      "Connection: Upgrade\r\n".
      "\r\n"
    );
  }

  #[Test]
  public function hint_uses_and_retains_previously_set_headers() {
    $res= new Response(new TestOutput());
    $res->header('Link', ['</main.css>; rel=preload; as=style', '</script.js>; rel=preload; as=script']);
    $res->hint(103);
    $res->answer(200, 'OK');
    $res->flush();

    Assert::that($res->output()->bytes())->isEqualTo(
      "HTTP/1.1 103 Early Hints\r\n".
      "Link: </main.css>; rel=preload; as=style\r\n".
      "Link: </script.js>; rel=preload; as=script\r\n".
      "\r\n".
      "HTTP/1.1 200 OK\r\n".
      "Link: </main.css>; rel=preload; as=style\r\n".
      "Link: </script.js>; rel=preload; as=script\r\n".
      "\r\n"
    );
  }

  #[Test]
  public function send_headers() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/plain');
    $res->header('Content-Length', '0');
    $res->flush();

    Assert::that($res->output()->bytes())->isEqualTo(
      "HTTP/1.1 200 OK\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 0\r\n".
      "\r\n"
    );
  }

  #[Test]
  public function send_html() {
    $res= new Response(new TestOutput());
    $res->send('<h1>Test</h1>', 'text/html');

    Assert::that($res->output()->bytes())->isEqualTo(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>"
    );
  }

  #[Test, Values(from: 'filenames')]
  public function send_file($name, $params) {
    $res= new Response(new TestOutput());
    $res->header('Content-Disposition', (new Parameterized('attachment'))->with('filename', $name));
    $res->flush();

    Assert::that($res->output()->bytes())->isEqualTo(
      "HTTP/1.1 200 OK\r\n".
      "Content-Disposition: attachment; {$params}\r\n".
      "\r\n"
    );
  }

  #[Test, Values(['uber.txt', 'über.txt'])]
  public function send_file_rfc8187_with_ascii_equivalent($name) {
    $res= new Response(new TestOutput());
    $res->header('Content-Disposition', (new Parameterized('attachment'))->with(
      'filename',
      $name,
      $equivalent= 'uber.txt'
    ));
    $res->flush();

    Assert::that($res->output()->bytes())->isEqualTo(
      "HTTP/1.1 200 OK\r\n".
      "Content-Disposition: attachment; filename=uber.txt; filename*=UTF-8''".rawurlencode($name)."\r\n".
      "\r\n"
    );
  }

  #[Test]
  public function transmit_stream_with_length() {
    $res= new Response(new TestOutput());
    foreach ($res->transmit(new MemoryInputStream('<h1>Test</h1>'), 'text/html', 13) as $_) { }

    Assert::that($res->output()->bytes())->isEqualTo(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>"
    );
  }

  #[Test]
  public function transmit_stream_chunked() {
    $res= new Response(new TestOutput());
    foreach ($res->transmit(new MemoryInputStream('<h1>Test</h1>'), 'text/html') as $_) { }

    Assert::that($res->output()->bytes())->isEqualTo(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nTransfer-Encoding: chunked\r\n\r\n".
      "d\r\n<h1>Test</h1>\r\n0\r\n\r\n"
    );
  }

  #[Test]
  public function transmit_stream_buffered() {
    $res= new Response(new TestOutput(Buffered::class));
    foreach ($res->transmit(new MemoryInputStream('<h1>Test</h1>'), 'text/html') as $_) { }

    Assert::that($res->output()->bytes())->isEqualTo(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>"
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

    Assert::that($res->output()->bytes())->isEqualTo(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>"
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

    Assert::that($res->output()->bytes())->isEqualTo(
      "HTTP/1.1 200 OK\r\n".
      "Content-Type: text/html\r\n".
      "Set-Cookie: toggle=future; SameSite=Lax; HttpOnly\r\n".
      "\r\n"
    );
  }

  #[Test]
  public function flushed() {
    $res= new Response(new TestOutput());
    Assert::false($res->flushed());
    $res->flush();
    Assert::true($res->flushed());
  }

  #[Test, Expect(IllegalStateException::class)]
  public function flush_twice() {
    $res= new Response(new TestOutput());
    $res->flush();
    $res->flush();
  }
}