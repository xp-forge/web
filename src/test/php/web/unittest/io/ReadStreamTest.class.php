<?php namespace web\unittest\io;

use unittest\{Test, TestCase};
use web\io\{ReadStream, TestInput};

class ReadStreamTest extends TestCase {

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
    $this->assertEquals(1, $fixture->available());
  }

  #[Test]
  public function read() {
    $fixture= new ReadStream($this->input('Test'));
    $this->assertEquals('Test', $fixture->read());
  }

  #[Test]
  public function close_is_a_noop() {
    $fixture= new ReadStream($this->input('Test'));
    $this->assertNull($fixture->close());
  }
}