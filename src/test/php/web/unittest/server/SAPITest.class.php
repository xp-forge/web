<?php namespace web\unittest\server;

use io\OperationNotSupportedException;
use io\streams\{Streams, MemoryInputStream};
use unittest\TestCase;
use web\io\{ReadStream, ReadLength};
use xp\web\SAPI;

class SAPITest extends TestCase {
  const BOUNDARY = '------------------------899f0c287170dd63';

  /**
   * Retrieve files for a given $_FILES layout
   *
   * @param  function(string): var[] $files
   * @return [:string]
   */
  private function parts($files) {
    $_FILES= $files(Streams::readableUri(new MemoryInputStream('Test')));
    return iterator_to_array((new SAPI())->parts(self::BOUNDARY));
  }

  /**
   * Returns a successful upload for use with `parts()`
   *
   * @param  string $name
   * @param  string $type
   * @return function(string): var[]
   */
  private function upload($name, $type) {
    return function($uri) use($name, $type) {
      return [
        'file' => [
          'name'     => $name,
          'type'     => $type,
          'tmp_name' => $uri,
          'error'    => UPLOAD_ERR_OK,
          'size'     => 4
        ]
      ];
    };
  }

  /**
   * Returns an incomplete transfer for use with `parts()`
   *
   * @param  string $name
   * @param  int $error
   * @return function(string): var[]
   */
  private function incomplete($name, $error) {
    return function($uri) use($name, $error) {
      return [
        'file' => [
          'name'     => $name,
          'type'     => '',
          'tmp_name' => '',
          'error'    => $error,
          'size'     => 0
        ]
      ];
    };
  }

  #[@test]
  public function can_create() {
    new SAPI();
  }

  #[@test]
  public function http_scheme_default() {
    $this->assertEquals('http', (new SAPI())->scheme());
  }

  #[@test, @values(['on', 'ON', '1'])]
  public function https_scheme_via_https_server_entry($value) {
    $_SERVER['HTTPS']= $value;
    $this->assertEquals('https', (new SAPI())->scheme());
  }

  #[@test, @values(['off', 'OFF', '0'])]
  public function http_scheme_via_https_server_entry($value) {
    $_SERVER['HTTPS']= $value;
    $this->assertEquals('http', (new SAPI())->scheme());
  }

  #[@test, @values(['GET', 'POST', 'OPTIONS'])]
  public function method($value) {
    $_SERVER['REQUEST_METHOD']= $value;
    $this->assertEquals($value, (new SAPI())->method());
  }

  #[@test]
  public function uri() {
    $_SERVER['REQUEST_URI']= '/favicon.ico';
    $this->assertEquals('/favicon.ico', (new SAPI())->uri());
  }

  #[@test]
  public function version() {
    $_SERVER['SERVER_PROTOCOL']= 'HTTP/1.1';
    $this->assertEquals('1.1', (new SAPI())->version());
  }

  #[@test]
  public function streamed_payload() {
    $_SERVER['HTTP_TRANSFER_ENCODING']= 'chunked';
    $_SERVER['REMOTE_ADDR']= '127.0.0.1';

    $fixture= new SAPI();
    iterator_count($fixture->headers());
    $this->assertInstanceOf(ReadStream::class, $fixture->incoming());
  }

  #[@test]
  public function payload_with_length() {
    $_SERVER['CONTENT_LENGTH']= '4';
    $_SERVER['REMOTE_ADDR']= '127.0.0.1';

    $fixture= new SAPI();
    iterator_count($fixture->headers());
    $this->assertInstanceOf(ReadLength::class, $fixture->incoming());
  }

  #[@test]
  public function parts_without_files() {
    $_FILES= [];
    $this->assertEquals([], iterator_to_array((new SAPI())->parts(self::BOUNDARY)));
  }

  #[@test]
  public function successful_upload() {
    $this->assertEquals(['file' => 'xp.web.Upload'], array_map(
      function($part) { return nameof($part); },
      $this->parts($this->upload('test.txt', 'text/plain'))
    ));
  }

  #[@test]
  public function successful_upload_with_array_parameter() {
    $this->assertEquals(['file[]' => 'xp.web.Upload'], array_map(
      function($part) { return nameof($part); },
      $this->parts(function($uri) {
        return [
          'file' => [
            'name'     => ['test.txt'],
            'type'     => ['text/plain'],
            'tmp_name' => [$uri],
            'error'    => [UPLOAD_ERR_OK],
            'size'     => [4]
          ]
        ];
      })
    ));
  }

  #[@test]
  public function upload_name() {
    $parts= $this->parts($this->upload('test.txt', 'text/plain'));
    $this->assertEquals('test.txt', $parts['file']->name());
  }

  #[@test]
  public function upload_type() {
    $parts= $this->parts($this->upload('test.txt', 'text/plain'));
    $this->assertEquals('text/plain', $parts['file']->type());
  }

  #[@test]
  public function read_part() {
    $parts= $this->parts($this->upload('test.txt', 'text/plain'));
    $this->assertEquals('Test', $parts['file']->bytes());
  }

  #[@test]
  public function use_part_as_stream() {
    $parts= $this->parts($this->upload('test.txt', 'text/plain'));
    $this->assertEquals('Test', Streams::readAll($parts['file']));
  }

  #[@test]
  public function upload_exceeding_ini_size() {
    $this->assertEquals(['file' => 'web.io.Incomplete("test.txt", error= ERR_INI_SIZE)'], array_map(
      function($part) { return $part->toString(); },
      $this->parts($this->incomplete('test.txt', UPLOAD_ERR_INI_SIZE))
    ));
  }

  #[@test]
  public function upload_without_file() {
    $this->assertEquals(['file' => 'web.io.Incomplete("", error= ERR_NO_FILE)'], array_map(
      function($part) { return $part->toString(); },
      $this->parts($this->incomplete('', UPLOAD_ERR_NO_FILE))
    ));
  }

  #[@test, @expect(OperationNotSupportedException::class)]
  public function read_from_incomplete_upload() {
    $parts= $this->parts($this->incomplete('test.txt', UPLOAD_ERR_PARTIAL));
    $parts['file']->bytes();
  }

  #[@test, @expect(OperationNotSupportedException::class)]
  public function stream_incomplete_upload() {
    $parts= $this->parts($this->incomplete('test.txt', UPLOAD_ERR_PARTIAL));
    Streams::readAll($parts['file']);
  }
}