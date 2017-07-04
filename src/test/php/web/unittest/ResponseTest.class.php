<?php namespace web\unittest;

use web\Response;
use web\Error;
use lang\IllegalStateException;
use io\streams\MemoryInputStream;

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
  public function no_error() {
    $res= new Response(new TestOutput());
    $res->error(null);
    $this->assertNull($res->error);
  }

  #[@test]
  public function error_status() {
    $res= new Response(new TestOutput());
    $res->error(403);
    $this->assertEquals('Error web.Error(#403: Forbidden)', $res->error->compoundMessage());
  }

  #[@test]
  public function error_status_with_message() {
    $res= new Response(new TestOutput());
    $res->error(403, 'Go away!');
    $this->assertEquals('Error web.Error(#403: Go away!)', $res->error->compoundMessage());
  }

  #[@test]
  public function error_message() {
    $res= new Response(new TestOutput());
    $res->error('Crash');
    $this->assertEquals('Error web.InternalServerError(#500: Crash)', $res->error->compoundMessage());
  }

  #[@test]
  public function error_cause() {
    $res= new Response(new TestOutput());
    $res->error(new IllegalStateException('Crash'));
    $this->assertEquals('Error web.InternalServerError(#500: Crash)', $res->error->compoundMessage());
  }

  #[@test]
  public function error_instance() {
    $res= new Response(new TestOutput());
    $res->error(new Error(402));
    $this->assertEquals('Error web.Error(#402: Payment Required)', $res->error->compoundMessage());
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

    $this->assertEquals($line."\r\n\r\n", $out->bytes);
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
      $out->bytes
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
      $out->bytes
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
      $out->bytes
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
      $out->bytes
    );
  }
}