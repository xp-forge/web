<?php namespace web\unittest\io;

use unittest\{Test, TestCase, Values};
use web\io\TestInput;
use web\unittest\Chunking;

class TestInputTest extends TestCase {
  use Chunking;

  #[Test]
  public function can_create() {
    new TestInput('GET', '/');
  }

  #[Test]
  public function version() {
    $this->assertEquals('1.1', (new TestInput('GET', '/'))->version());
  }

  #[Test]
  public function scheme() {
    $this->assertEquals('http', (new TestInput('GET', '/'))->scheme());
  }

  #[Test, Values(['GET', 'HEAD', 'POST'])]
  public function method($name) {
    $this->assertEquals($name, (new TestInput($name, '/'))->method());
  }

  #[Test, Values(['/', '/test'])]
  public function uri($path) {
    $this->assertEquals($path, (new TestInput('GET', $path))->uri());
  }

  #[Test]
  public function headers_empty_by_default() {
    $this->assertEquals([], (new TestInput('GET', '/'))->headers());
  }

  #[Test]
  public function headers() {
    $headers= ['Host' => 'example.com'];
    $this->assertEquals($headers, (new TestInput('GET', '/', $headers))->headers());
  }

  #[Test]
  public function body_empty_by_default() {
    $this->assertEquals('', (new TestInput('GET', '/'))->read());
  }

  #[Test, Values(['', 'body'])]
  public function read($body) {
    $this->assertEquals($body, (new TestInput('GET', '/', [], $body))->read());
  }

  #[Test]
  public function reading_line() {
    $this->assertEquals('line', (new TestInput('GET', '/', [], "line\r\n"))->readLine());
  }

  #[Test]
  public function reading_lines() {
    $fixture= new TestInput('GET', '/', [], "line 1\r\nline 2\r\n");
    $this->assertEquals('line 1', $fixture->readLine());
    $this->assertEquals('line 2', $fixture->readLine());
    $this->assertNull($fixture->readLine());
  }

  #[Test]
  public function content_length_calculated() {
    $fixture= new TestInput('GET', '/', ['Content-Type' => 'text/plain'], 'Test');
    $this->assertEquals(['Content-Type' => 'text/plain', 'Content-Length' => 4], $fixture->headers());
  }

  #[Test]
  public function content_length_not_calculated_when_chunked() {
    $fixture= new TestInput('GET', '/', self::$CHUNKED, $this->chunked('Test'));
    $this->assertEquals(self::$CHUNKED, $fixture->headers());
  }

  #[Test]
  public function body_can_be_passed_as_array() {
    $fixture= new TestInput('GET', '/', ['Accept' => '*/*'], ['key' => 'value']);
    $this->assertEquals('key=value', $fixture->read());
    $this->assertEquals(
      ['Accept' => '*/*', 'Content-Type' => 'application/x-www-form-urlencoded', 'Content-Length' => 9],
      $fixture->headers()
    );
  }
}