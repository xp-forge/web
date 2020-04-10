<?php namespace web\unittest\io;

use unittest\TestCase;
use web\io\{Buffered, TestOutput};

class TestOutputTest extends TestCase {

  #[@test]
  public function can_create() {
    new TestOutput();
  }

  #[@test]
  public function begin() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', []);
    $this->assertEquals("HTTP/1.1 200 OK\r\n\r\n", $fixture->bytes());
  }

  #[@test]
  public function begin_with_header() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', ['Server' => ['Test']]);
    $this->assertEquals("HTTP/1.1 200 OK\r\nServer: Test\r\n\r\n", $fixture->bytes());
  }

  #[@test]
  public function begin_with_404_status() {
    $fixture= new TestOutput();
    $fixture->begin(404, 'Not Found', []);
    $this->assertEquals("HTTP/1.1 404 Not Found\r\n\r\n", $fixture->bytes());
  }

  #[@test]
  public function write() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', ['Content-Length' => [4]]);
    $fixture->write('Test');
    $this->assertEquals("HTTP/1.1 200 OK\r\nContent-Length: 4\r\n\r\nTest", $fixture->bytes());
  }

  #[@test]
  public function start() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', []);
    $this->assertEquals('HTTP/1.1 200 OK', $fixture->start());

  }

  #[@test]
  public function empty_headers() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', []);
    $this->assertEquals('', $fixture->headers());
  }

  #[@test]
  public function headers() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', ['Server' => ['Test'], 'Cache-control' => ['no-cache']]);
    $this->assertEquals("Server: Test\r\nCache-control: no-cache", $fixture->headers());
  }

  #[@test]
  public function body() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', ['Content-Length' => [4]]);
    $fixture->write('Test');
    $this->assertEquals('Test', $fixture->body());
  }

  #[@test]
  public function chunked_stream() {
    $fixture= new TestOutput();
    with ($fixture->stream(), function($stream) {
      $stream->begin(200, 'OK', []);
      $stream->write('Hello');
      $stream->write('Test');
    });
    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nTransfer-Encoding: chunked\r\n\r\n9\r\nHelloTest\r\n0\r\n\r\n",
      $fixture->bytes()
    );
  }

  #[@test]
  public function buffered_stream() {
    $fixture= new TestOutput(Buffered::class);
    with ($fixture->stream(), function($stream) {
      $stream->begin(200, 'OK', []);
      $stream->write('Hello');
      $stream->write('Test');
    });
    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nContent-Length: 9\r\n\r\nHelloTest",
      $fixture->bytes()
    );
  }

  #[@test]
  public function chunked_stream_via_constructor() {
    $fixture= TestOutput::chunked();
    with ($fixture->stream(), function($stream) {
      $stream->begin(200, 'OK', []);
      $stream->write('Hello');
      $stream->write('Test');
    });
    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nTransfer-Encoding: chunked\r\n\r\n9\r\nHelloTest\r\n0\r\n\r\n",
      $fixture->bytes()
    );
  }

  #[@test]
  public function buffered_stream_via_constructor() {
    $fixture= TestOutput::buffered();
    with ($fixture->stream(), function($stream) {
      $stream->begin(200, 'OK', []);
      $stream->write('Hello');
      $stream->write('Test');
    });
    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nContent-Length: 9\r\n\r\nHelloTest",
      $fixture->bytes()
    );
  }

  /** @deprecated */
  #[@test]
  public function buffered_stream_with_using() {
    $fixture= (new TestOutput())->using(Buffered::class);
    with ($fixture->stream(), function($stream) {
      $stream->begin(200, 'OK', []);
      $stream->write('Hello');
      $stream->write('Test');
    });
    $this->assertEquals(
      "HTTP/1.1 200 OK\r\nContent-Length: 9\r\n\r\nHelloTest",
      $fixture->bytes()
    );
  }
}