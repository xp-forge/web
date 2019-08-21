<?php namespace web\unittest\io;

use unittest\TestCase;
use web\io\TestInput;

class TestInputTest extends TestCase {

  #[@test]
  public function can_create() {
    new TestInput('GET', '/');
  }

  #[@test]
  public function version() {
    $this->assertEquals('1.1', (new TestInput('GET', '/'))->version());
  }

  #[@test]
  public function scheme() {
    $this->assertEquals('http', (new TestInput('GET', '/'))->scheme());
  }

  #[@test, @values(['GET', 'HEAD', 'POST'])]
  public function method($name) {
    $this->assertEquals($name, (new TestInput($name, '/'))->method());
  }

  #[@test, @values(['/', '/test'])]
  public function uri($path) {
    $this->assertEquals($path, (new TestInput('GET', $path))->uri());
  }

  #[@test]
  public function headers_empty_by_default() {
    $this->assertEquals([], (new TestInput('GET', '/'))->headers());
  }

  #[@test]
  public function headers() {
    $headers= ['Host' => 'example.com'];
    $this->assertEquals($headers, (new TestInput('GET', '/', $headers))->headers());
  }

  #[@test]
  public function body_empty_by_default() {
    $this->assertEquals('', (new TestInput('GET', '/'))->read());
  }

  #[@test, @values(['', 'body'])]
  public function read($body) {
    $this->assertEquals($body, (new TestInput('GET', '/', [], $body))->read());
  }

  #[@test]
  public function reading_line() {
    $this->assertEquals('line', (new TestInput('GET', '/', [], "line\n"))->readLine());
  }

  #[@test]
  public function reading_lines() {
    $fixture= new TestInput('GET', '/', [], "line 1\nline 2\n");
    $this->assertEquals('line 1', $fixture->readLine());
    $this->assertEquals('line 2', $fixture->readLine());
    $this->assertEquals('', $fixture->readLine());
  }
}