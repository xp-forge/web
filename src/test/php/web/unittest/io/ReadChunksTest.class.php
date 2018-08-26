<?php namespace web\unittest\io;

use lang\FormatException;
use web\io\ReadChunks;
use web\io\TestInput;

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

  #[@test, @values([
  #  ["0\r\n\r\n", []],
  #  ["4\r\nTest\r\n0\r\n\r\n", ['Test']],
  #  ["e\r\n{\"name\":\"PHP\"}\r\n0\r\n\r\n", ['{"name":"PHP"}']],
  #  ["F\r\n{\"name\":\"JSON\"}\r\n0\r\n\r\n", ['{"name":"JSON"}']],
  #])]
  public function while_loop($chunked, $expected) {
    $fixture= new ReadChunks(new TestInput('GET', '/', [], $chunked));
    $r= [];
    while ($fixture->available()) {
      $r[]= $fixture->read();
    }
    $this->assertEquals($expected, $r);
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

  #[@test]
  public function read_invocations() {
    $input= newinstance(TestInput::class, ['GET', '/', [], "4\r\nTest\r\n2\r\ned\r\n0\r\n\r\n"], [
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
        'readLine:"4\r"', 'read:4:"Test"', 'readLine:"\r"',
        'readLine:"2\r"', 'read:2:"ed"', 'readLine:"\r"',
        'readLine:"0\r"', 'readLine:"\r"'
      ],
      $input->invocations
    );
  }
}
