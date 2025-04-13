<?php namespace web\unittest\io;

use test\{Assert, Test, Values};
use web\io\TestInput;
use web\unittest\Chunking;

class TestInputTest {
  use Chunking;

  #[Test]
  public function can_create() {
    new TestInput('GET', '/');
  }

  #[Test]
  public function version() {
    Assert::equals('1.1', (new TestInput('GET', '/'))->version());
  }

  #[Test]
  public function scheme() {
    Assert::equals('http', (new TestInput('GET', '/'))->scheme());
  }

  #[Test, Values(['GET', 'HEAD', 'POST'])]
  public function method($name) {
    Assert::equals($name, (new TestInput($name, '/'))->method());
  }

  #[Test, Values(['/', '/test', '/?q=Test'])]
  public function resource($path) {
    Assert::equals($path, (new TestInput('GET', $path))->resource());
  }

  #[Test]
  public function headers_empty_by_default() {
    Assert::equals([], (new TestInput('GET', '/'))->headers());
  }

  #[Test]
  public function headers() {
    $headers= ['Host' => 'example.com'];
    Assert::equals($headers, (new TestInput('GET', '/', $headers))->headers());
  }

  #[Test]
  public function body_empty_by_default() {
    Assert::equals('', (new TestInput('GET', '/'))->read());
  }

  #[Test, Values(['', 'body'])]
  public function read($body) {
    Assert::equals($body, (new TestInput('GET', '/', [], $body))->read());
  }

  #[Test]
  public function reading_line() {
    Assert::equals('line', (new TestInput('GET', '/', [], "line\r\n"))->readLine());
  }

  #[Test]
  public function reading_line_without_ending_crlf() {
    Assert::equals('line', (new TestInput('GET', '/', [], "line"))->readLine());
  }

  #[Test]
  public function reading_lines() {
    $fixture= new TestInput('GET', '/', [], "line 1\r\nline 2\r\n");
    Assert::equals('line 1', $fixture->readLine());
    Assert::equals('line 2', $fixture->readLine());
    Assert::null($fixture->readLine());
  }

  #[Test]
  public function content_length_calculated() {
    $fixture= new TestInput('GET', '/', ['Content-Type' => 'text/plain'], 'Test');
    Assert::equals(['Content-Type' => 'text/plain', 'Content-Length' => 4], $fixture->headers());
  }

  #[Test]
  public function content_length_not_calculated_when_chunked() {
    $fixture= new TestInput('GET', '/', self::$CHUNKED, $this->chunked('Test'));
    Assert::equals(self::$CHUNKED, $fixture->headers());
  }

  #[Test]
  public function body_can_be_passed_as_array() {
    $fixture= new TestInput('GET', '/', ['Accept' => '*/*'], ['key' => 'value']);
    Assert::equals('key=value', $fixture->read());
    Assert::equals(
      ['Accept' => '*/*', 'Content-Type' => 'application/x-www-form-urlencoded', 'Content-Length' => 9],
      $fixture->headers()
    );
  }
}