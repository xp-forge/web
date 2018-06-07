<?php namespace web\unittest;

use web\Cookie;
use web\Response;
use io\streams\MemoryInputStream;
use util\URI;
use web\io\TestOutput;
use web\io\Buffered;

class ResponseTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Response(new TestOutput());
  }

  #[@test]
  public function status_initially_200() {
    $res= new Response(new TestOutput());
    $this->assertEquals(200, $res->status());
  }

  #[@test]
  public function status() {
    $res= new Response(new TestOutput());
    $res->answer(201);
    $this->assertEquals(201, $res->status());
  }

  #[@test]
  public function message() {
    $res= new Response(new TestOutput());
    $res->answer(201, 'Created');
    $this->assertEquals('Created', $res->message());
  }

  #[@test]
  public function custom_message() {
    $res= new Response(new TestOutput());
    $res->answer(201, 'Creation succeeded');
    $this->assertEquals('Creation succeeded', $res->message());
  }

  #[@test]
  public function headers_initially_empty() {
    $res= new Response(new TestOutput());
    $this->assertEquals([], $res->headers());
  }

  #[@test]
  public function header() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/plain');
    $this->assertEquals(['Content-Type' => 'text/plain'], $res->headers());
  }

  #[@test]
  public function headers() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/plain');
    $res->header('Content-Length', '0');
    $this->assertEquals(
      ['Content-Type' => 'text/plain', 'Content-Length' => '0'],
      $res->headers()
    );
  }

  #[@test]
  public function remove_header() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/plain');
    $res->header('Content-Type', null);
    $this->assertEquals([], $res->headers());
  }

  #[@test]
  public function multiple_header() {
    $res= new Response(new TestOutput());
    $res->header('Set-Cookie', ['theme=light', 'sessionToken=abc123']);
    $this->assertEquals(['Set-Cookie' => ['theme=light', 'sessionToken=abc123']], $res->headers());
  }

  #[@test]
  public function append_header() {
    $res= new Response(new TestOutput());
    $res->header('Set-Cookie', 'theme=light', true);
    $res->header('Set-Cookie', 'sessionToken=abc123', true);
    $this->assertEquals(['Set-Cookie' => ['theme=light', 'sessionToken=abc123']], $res->headers());
  }

  #[@test]
  public function uri_header() {
    $res= new Response(new TestOutput());
    $res->header('Location', new URI('http://example.com/'));
    $this->assertEquals(['Location' => 'http://example.com/'], $res->headers());
  }

  #[@test]
  public function cookie() {
    $res= new Response(new TestOutput());
    $res->cookie(new Cookie('theme', 'light'));
    $this->assertEquals('light', $res->cookies()[0]->value());
  }

  #[@test]
  public function cookies() {
    $cookies= [new Cookie('theme', 'Test'), (new Cookie('sessionToken', 'abc123'))->expires('Wed, 09 Jun 2021 10:18:14 GMT')];

    $res= new Response(new TestOutput());
    foreach ($cookies as $cookie) {
      $res->cookie($cookie);
    }

    $this->assertEquals($cookies, $res->cookies());
  }

  #[@test, @values([
  #  [200, 'OK', 'HTTP/1.1 200 OK'],
  #  [404, 'Not Found', 'HTTP/1.1 404 Not Found'],
  #  [200, null, 'HTTP/1.1 200 OK'],
  #  [404, null, 'HTTP/1.1 404 Not Found'],
  #  [200, 'Okay', 'HTTP/1.1 200 Okay'],
  #  [404, 'Nope', 'HTTP/1.1 404 Nope']
  #])]
  public function answer($status, $message, $line) {
    $out= new TestOutput();

    $res= new Response($out);
    $res->answer($status, $message);
    $res->flush();

    $this->assertEquals($line."\r\n\r\n", $out->bytes());
  }

  #[@test]
  public function send_headers() {
    $out= new TestOutput();

    $res= new Response($out);
    $res->header('Content-Type', 'text/plain');
    $res->header('Content-Length', '0');
    $res->flush();

    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nContent-Length: 0\r\n\r\n",
      $out->bytes()
    );
  }

  #[@test]
  public function send_html() {
    $out= new TestOutput();

    $res= new Response($out);
    $res->send('<h1>Test</h1>', 'text/html');

    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>",
      $out->bytes()
    );
  }

  #[@test]
  public function transfer_stream_with_length() {
    $out= new TestOutput();

    $res= new Response($out);
    $res->transfer(new MemoryInputStream('<h1>Test</h1>'), 'text/html', 13);

    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>",
      $out->bytes()
    );
  }

  #[@test]
  public function transfer_stream_chunked() {
    $out= new TestOutput();

    $res= new Response($out);
    $res->transfer(new MemoryInputStream('<h1>Test</h1>'), 'text/html');

    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nTransfer-Encoding: chunked\r\n\r\n".
      "d\r\n<h1>Test</h1>\r\n0\r\n\r\n",
      $out->bytes()
    );
  }

  #[@test]
  public function transfer_stream_buffered() {
    $out= (new TestOutput())->using(Buffered::class);

    $res= new Response($out);
    $res->transfer(new MemoryInputStream('<h1>Test</h1>'), 'text/html');

    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nContent-Length: 13\r\n\r\n".
      "<h1>Test</h1>",
      $out->bytes()
    );
  }

  #[@test]
  public function cookies_and_headers_are_merged() {
    $res= new Response(new TestOutput());
    $res->header('Content-Type', 'text/html');
    $res->cookie(new Cookie('toggle', 'future'));
    $res->flush();

    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nSet-Cookie: toggle=future; SameSite=Lax; HttpOnly\r\n\r\n",
      $res->output()->bytes()
    );
  }
}