<?php namespace web\unittest;

use io\streams\Streams;
use unittest\TestCase;
use web\Request;
use web\io\{TestInput, Multipart};

class MultipartRequestTest extends TestCase {
  use Chunking;

  const BOUNDARY = '------------------------899f0c287170dd63';

  private static $MULTIPART = ['Content-Type' => 'multipart/form-data; boundary='.self::BOUNDARY];

  /**
   * Creates multipart/form-data payload from an array
   *
   * @see    https://tools.ietf.org/html/rfc7578
   * @param  string[][] $parts As produced by file() and param()
   * @return string
   */
  private function multipart($parts) {
    $body= '';
    foreach ($parts as $part) {
      $body.= '--'.self::BOUNDARY."\r\n".implode("\r\n", $part)."\r\n";
    }
    return $body.'--'.self::BOUNDARY."--\r\n";
  }

  /**
   * Returns a file
   *
   * @param  string $filename
   * @param  string $bytes
   * @return string[]
   */
  private function file($filename, $bytes) {
    return [
      'Content-Disposition: form-data; name="file"; filename="'.$filename.'"',
      'Content-Type: application/octet-stream',
      '',
      $bytes
    ];
  }

  /**
   * Returns a parameter
   *
   * @param  string $name
   * @param  string $bytes
   * @return string[]
   */
  private function param($name, $value) {
    return [
      'Content-Disposition: form-data; name="'.$name.'"',
      '',
      $value
    ];
  }

  /**
   * Supplies files for upload test
   *
   * @return iterable
   */ 
  private function files() {
    yield ['.empty', ''];
    yield ['test.txt', 'Test'];
    yield ['unix.txt', "Line 1\nLine 2"];
    yield ['mac.txt', "Line 1\rLine 2"];
    yield ['windows.txt', "Line 1\r\nLine 2"];
    yield ['blank.gif', "GIF89a\1\0\1\0\200\0\0\0\0\0\377\377\377\!\371\4\1\0\0\0\0,\0\0\0\0\1\0\1\0@\2\1D\0;"];
  }

  #[@test, @values('files')]
  public function files_in_file_upload($filename, $bytes) {
    $req= new Request(new TestInput('POST', '/', self::$MULTIPART, $this->multipart([
      $this->file($filename, $bytes),
      $this->param('submit', 'Upload')
    ])));
    $files= [];
    foreach ($req->files() as $name => $file) {
      $files[$name]= addcslashes($file->bytes(), "\0..\37\177..\377");
    }
    $this->assertEquals([$filename => addcslashes($bytes, "\0..\37\177..\377")], $files);
  }

  #[@test, @values('files')]
  public function tmp_files_lifecycle($filename, $bytes) {
    $req= new Request(new TestInput('POST', '/', self::$MULTIPART, $this->multipart([
      $this->file($filename, $bytes),
      $this->param('submit', 'Upload')
    ])));

    // Created
    $filenames= [];
    foreach ($req->files() as $name => $file) {
      $this->assertTrue(file_exists($filenames[]= $file->source()));
    }

    // Delete after destruct
    unset ($req);
    foreach ($filenames as $filename) {
      $this->assertFalse(file_exists($filename));
    }
  }

  #[@test]
  public function multiple_params() {
    $req= new Request(new TestInput('POST', '/', self::$MULTIPART, $this->multipart([
      $this->param('tc', 'Checked'),
      $this->param('submit', 'Upload')
    ])));

    $this->assertEquals(['tc' => 'Checked', 'submit' => 'Upload'], $req->params());
  }

  #[@test, @values([
  #  ['/', []],
  #  ['/?a=b', ['a' => 'b']],
  #  ['/?a=b&c=d', ['a' => 'b', 'c' => 'd']],
  #])]
  public function params_merged_with_request($uri, $params) {
    $req= new Request(new TestInput('POST', $uri, self::$MULTIPART, $this->multipart([
      $this->param('tc', 'Checked'),
      $this->param('submit', 'Upload')
    ])));

    $this->assertEquals($params + ['tc' => 'Checked', 'submit' => 'Upload'], $req->params());
  }

  #[@test]
  public function array_parameters_merged() {
    $req= new Request(new TestInput('POST', '/', self::$MULTIPART, $this->multipart([
      $this->param('accepted[]', 'tc'),
      $this->param('accepted[]', 'privacy'),
      $this->param('submit', 'Upload')
    ])));

    $this->assertEquals(['accepted' => ['tc', 'privacy'], 'submit' => 'Upload'], $req->params());
  }

  #[@test]
  public function map_parameters__merged() {
    $req= new Request(new TestInput('POST', '/', self::$MULTIPART, $this->multipart([
      $this->param('accepted[tc]', 'true'),
      $this->param('accepted[privacy]', 'true'),
      $this->param('submit', 'Upload')
    ])));

    $this->assertEquals(['accepted' => ['tc' => 'true', 'privacy' => 'true'], 'submit' => 'Upload'], $req->params());
  }


  #[@test, @values([0, 4, 0xff, 0x100, 0xffff])]
  public function can_process_chunked_multipart_formdata($length) {
    $bytes= str_repeat('*', $length);
    $chunked= $this->chunked($this->multipart([$this->file('test.txt', $bytes)]), 0xff);
    $req= new Request(new TestInput('POST', '/', self::$MULTIPART + self::$CHUNKED, $chunked));

    $files= [];
    foreach ($req->files() as $name => $file) {
      $files[$name]= strlen($file->bytes());
    }
    $this->assertEquals(['test.txt' => $length], $files);
  }
}