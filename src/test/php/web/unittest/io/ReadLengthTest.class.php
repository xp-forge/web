<?php namespace web\unittest\io;

use io\IOException;
use test\{Assert, Test, Values};
use web\io\{ReadLength, TestInput};

class ReadLengthTest {

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
    new ReadLength($this->input(''), 0);
  }

  #[Test]
  public function available() {
    $fixture= new ReadLength($this->input('Test'), 4);
    Assert::equals(4, $fixture->available());
  }

  #[Test]
  public function read() {
    $fixture= new ReadLength($this->input('Test'), 4);
    Assert::equals('Test', $fixture->read());
  }

  #[Test]
  public function available_when_empty() {
    $fixture= new ReadLength($this->input(''), 0);
    Assert::equals(0, $fixture->available());
  }

  #[Test]
  public function available_after_read() {
    $fixture= new ReadLength($this->input('Test'), 4);
    $fixture->read();
    Assert::equals(0, $fixture->available());
  }

  #[Test, Values([4, 8192])]
  public function reading_after_eof_raises_exception($length) {
    $fixture= new ReadLength($this->input('Test'), 4);
    $fixture->read($length);

    try {
      $fixture->read(1);
      $this->fail('No exception raised', null, IOException::class);
    } catch (IOException $expected) { }
  }

  #[Test]
  public function close_is_a_noop() {
    $fixture= new ReadLength($this->input('Test'), 4);
    Assert::null($fixture->close());
  }
}