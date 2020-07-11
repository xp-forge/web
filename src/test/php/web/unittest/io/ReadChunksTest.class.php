<?php namespace web\unittest\io;

use io\IOException;
use unittest\TestCase;
use web\io\ReadChunks;
use web\io\TestInput;
use web\unittest\Chunking;

class ReadChunksTest extends TestCase {
  use Chunking;

  /**
   * Creates an input instance
   *
   * @param  string $bytes
   * @return web.io.Input
   */
  private function input($bytes) {
    return new TestInput('POST', '/', self::$CHUNKED, $bytes);
  }

  #[@test]
  public function can_create() {
    new ReadChunks($this->input("0\r\n\r\n"));
  }

  #[@test, @expect(IOException::class)]
  public function raises_exception_from_read_when_non_chunked_data_appears() {
    $fixture= new ReadChunks($this->input(''));
    $fixture->read();
  }

  #[@test, @expect(IOException::class)]
  public function raises_exception_from_available_when_non_chunked_data_appears() {
    $fixture= new ReadChunks($this->input(''));
    $fixture->available();
  }

  #[@test]
  public function does_not_raise_exception_until_read_or_available_accessed() {
    new ReadChunks($this->input(''));
  }

  #[@test]
  public function available() {
    $fixture= new ReadChunks($this->input("4\r\nTest\r\n0\r\n\r\n"));
    $this->assertEquals(4, $fixture->available());
  }

  #[@test, @values([
  #  [2, 'Te', 2],
  #  [4, 'Test', 6],
  #  [6, 'Test', 6],
  #])]
  public function available_after_reading($length, $read, $remaining) {
    $fixture= new ReadChunks($this->input("4\r\nTest\r\n6\r\nTested\r\n0\r\n\r\n"));
    $this->assertEquals($read, $fixture->read($length));
    $this->assertEquals($remaining, $fixture->available());
  }

  #[@test]
  public function available_last_chunk() {
    $fixture= new ReadChunks($this->input("0\r\n\r\n"));
    $this->assertEquals(0, $fixture->available());
  }

  #[@test]
  public function read() {
    $fixture= new ReadChunks($this->input("4\r\nTest\r\n0\r\n\r\n"));
    $this->assertEquals('Test', $fixture->read());
  }

  #[@test, @values([
  #  ["0\r\n\r\n", []],
  #  ["4\r\nTest\r\n0\r\n\r\n", ['Test']],
  #  ["4\r\nTest\r\n2\r\nOK\r\n0\r\n\r\n", ['Test', 'OK']],
  #  ["6\r\nTest\r\n\r\n0\r\n\r\n", ["Test\r\n"]],
  #  ["e\r\n{\"name\":\"PHP\"}\r\n0\r\n\r\n", ['{"name":"PHP"}']],
  #  ["F\r\n{\"name\":\"JSON\"}\r\n0\r\n\r\n", ['{"name":"JSON"}']],
  #])]
  public function chunks($chunked, $expected) {
    $fixture= new ReadChunks($this->input($chunked));
    $r= [];
    while ($fixture->available()) {
      $r[]= $fixture->read();
    }
    $this->assertEquals($expected, $r);
  }

  #[@test]
  public function read_until_end() {
    $input= $this->input("4\r\nTest\r\n0\r\n\r\n");
    $fixture= new ReadChunks($input);
    while ($fixture->available()) {
      $fixture->read();
    }
    $this->assertEquals('', $input->read(-1));
  }

  #[@test]
  public function read_invocations() {
    $input= newinstance(TestInput::class, ['POST', '/', self::$CHUNKED, "4\r\nTest\r\n2\r\ned\r\n0\r\n\r\n"], [
      'invocations' => [],
      'read' => function($length= -1) {
        $result= parent::read($length);
        $this->invocations[]= 'read:'.$length.':"'.addcslashes($result, "\r\n").'"';
        return $result;
      },
      'readLine' => function() {
        $result= parent::readLine();
        $this->invocations[]= 'readLine:"'.addcslashes($result, "\r\n").'"';
        return $result;
      }
    ]);
    $fixture= new ReadChunks($input);
    while ($fixture->available()) {
      $fixture->read();
    }

    $this->assertEquals(
      [
        'readLine:"4"', 'read:4:"Test"', 'readLine:""',
        'readLine:"2"', 'read:2:"ed"', 'readLine:""',
        'readLine:"0"', 'readLine:""'
      ],
      $input->invocations
    );
  }
}
