<?php namespace web\unittest\io;

use unittest\{Test, TestCase};
use web\io\{Buffered, TestOutput};

class TestOutputTest extends TestCase {

  #[Test]
  public function can_create() {
    new TestOutput();
  }

  #[Test]
  public function begin() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', []);
    $this->assertEquals("HTTP/1.1 200 OK\r\n\r\n", $fixture->bytes());
  }

  #[Test]
  public function begin_with_header() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', ['Server' => ['Test']]);
    $this->assertEquals("HTTP/1.1 200 OK\r\nServer: Test\r\n\r\n", $fixture->bytes());
  }

  #[Test]
  public function begin_with_404_status() {
    $fixture= new TestOutput();
    $fixture->begin(404, 'Not Found', []);
    $this->assertEquals("HTTP/1.1 404 Not Found\r\n\r\n", $fixture->bytes());
  }

  #[Test]
  public function write() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', ['Content-Length' => [4]]);
    $fixture->write('Test');
    $this->assertEquals("HTTP/1.1 200 OK\r\nContent-Length: 4\r\n\r\nTest", $fixture->bytes());
  }

  #[Test]
  public function start() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', []);
    $this->assertEquals('HTTP/1.1 200 OK', $fixture->start());

  }

  #[Test]
  public function empty_headers() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', []);
    $this->assertEquals('', $fixture->headers());
  }

  #[Test]
  public function headers() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', ['Server' => ['Test'], 'Cache-control' => ['no-cache']]);
    $this->assertEquals("Server: Test\r\nCache-control: no-cache", $fixture->headers());
  }

  #[Test]
  public function body() {
    $fixture= new TestOutput();
    $fixture->begin(200, 'OK', ['Content-Length' => [4]]);
    $fixture->write('Test');
    $this->assertEquals('Test', $fixture->body());
  }

  #[Test]
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

  #[Test]
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

  #[Test]
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

  #[Test]
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
  #[Test]
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