<?php namespace web\unittest\io;

use web\io\{TestOutput, WriteChunks};

class WriteChunksTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new WriteChunks(new TestOutput());
  }

  #[@test]
  public function write_one_chunk() {
    $out= new TestOutput();

    $w= new WriteChunks($out);
    $w->write('Test');
    $w->finish();

    $this->assertEquals("4\r\nTest\r\n0\r\n\r\n", $out->bytes());
  }

  #[@test]
  public function write_two_small_chunks() {
    $out= new TestOutput();

    $w= new WriteChunks($out);
    $w->write('Unit');
    $w->write('Test');
    $w->finish();

    $this->assertEquals("8\r\nUnitTest\r\n0\r\n\r\n", $out->bytes());
  }

  #[@test]
  public function write_chunk_exceeding_buffer_size() {
    $out= new TestOutput();
    $chunk= str_repeat('*', WriteChunks::BUFFER_SIZE + 1);

    $w= new WriteChunks($out);
    $w->write($chunk);
    $w->write('Test');
    $w->finish();

    $this->assertEquals("1001\r\n".$chunk."\r\n4\r\nTest\r\n0\r\n\r\n", $out->bytes());
  }
}