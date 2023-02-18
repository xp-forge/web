<?php namespace web\unittest\io;

use test\{Assert, Test};
use web\io\{ReadStream, TestInput};

class ReadStreamTest {

  /**
   * Creates an input instance
   *
   * @param  string $bytes
   * @return web.io.Input
   */
  private function input($bytes) {
    return new TestInput('POST', '/', [], $bytes);
  }

  #[Test]
  public function can_create() {
    new ReadStream($this->input(''));
  }

  #[Test]
  public function available() {
    $fixture= new ReadStream($this->input('Test'));
    Assert::equals(1, $fixture->available());
  }

  #[Test]
  public function read() {
    $fixture= new ReadStream($this->input('Test'));
    Assert::equals('Test', $fixture->read());
  }

  #[Test]
  public function close_is_a_noop() {
    $fixture= new ReadStream($this->input('Test'));
    Assert::null($fixture->close());
  }
}