<?php namespace web\unittest\io;

use unittest\{Test, TestCase};
use web\io\{ReadLength, TestInput};

class ReadLengthTest extends TestCase {

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
    $this->assertEquals(4, $fixture->available());
  }

  #[Test]
  public function read() {
    $fixture= new ReadLength($this->input('Test'), 4);
    $this->assertEquals('Test', $fixture->read());
  }

  #[Test]
  public function available_when_empty() {
    $fixture= new ReadLength($this->input(''), 0);
    $this->assertEquals(0, $fixture->available());
  }

  #[Test]
  public function available_after_read() {
    $fixture= new ReadLength($this->input('Test'), 4);
    $fixture->read();
    $this->assertEquals(0, $fixture->available());
  }
}