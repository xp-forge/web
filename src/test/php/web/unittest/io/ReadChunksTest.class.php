<?php namespace web\unittest\io;

use web\io\ReadChunks;
use web\io\TestInput;
use lang\FormatException;

class ReadChunksTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new ReadChunks(new TestInput('GET', '/', [], "0\r\n\r\n"));
  }

  #[@test, @expect(FormatException::class)]
  public function raises_exception_when_non_chunked_data_appears() {
    new ReadChunks(new TestInput('GET', '/', [], ''));
  }

  #[@test]
  public function available() {
    $fixture= new ReadChunks(new TestInput('GET', '/', [], "4\r\nTest\r\n0\r\n\r\n"));
    $this->assertEquals(4, $fixture->available());
  }

  #[@test]
  public function available_last_chunk() {
    $fixture= new ReadChunks(new TestInput('GET', '/', [], "0\r\n\r\n"));
    $this->assertEquals(0, $fixture->available());
  }

  #[@test]
  public function read() {
    $fixture= new ReadChunks(new TestInput('GET', '/', [], "4\r\nTest\r\n0\r\n\r\n"));
    $this->assertEquals('Test', $fixture->read());
  }

  #[@test]
  public function while_loop() {
    $fixture= new ReadChunks(new TestInput('GET', '/', [], "4\r\nTest\r\n0\r\n\r\n"));
    $r= [];
    while ($fixture->available()) {
      $r[]= $fixture->read();
    }
    $this->assertEquals(['Test'], $r);
  }

  #[@test]
  public function read_until_end() {
    $input= new TestInput('GET', '/', [], "4\r\nTest\r\n0\r\n\r\n");
    $fixture= new ReadChunks($input);
    while ($fixture->available()) {
      $fixture->read();
    }
    $this->assertEquals('', $input->read(-1));
  }
}
