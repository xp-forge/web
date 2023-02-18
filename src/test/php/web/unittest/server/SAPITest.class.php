<?php namespace web\unittest\server;

use io\OperationNotSupportedException;
use io\streams\{MemoryInputStream, Streams};
use test\{Assert, Expect, Test, Values};
use web\io\{ReadLength, ReadStream};
use xp\web\SAPI;

class SAPITest {
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

  #[Test]
  public function can_create() {
    new SAPI();
  }

  #[Test]
  public function http_scheme_default() {
    Assert::equals('http', (new SAPI())->scheme());
  }

  #[Test, Values(['on', 'ON', '1'])]
  public function https_scheme_via_https_server_entry($value) {
    $_SERVER['HTTPS']= $value;
    Assert::equals('https', (new SAPI())->scheme());
  }

  #[Test, Values(['off', 'OFF', '0'])]
  public function http_scheme_via_https_server_entry($value) {
    $_SERVER['HTTPS']= $value;
    Assert::equals('http', (new SAPI())->scheme());
  }

  #[Test, Values(['GET', 'POST', 'OPTIONS'])]
  public function method($value) {
    $_SERVER['REQUEST_METHOD']= $value;
    Assert::equals($value, (new SAPI())->method());
  }

  #[Test]
  public function uri() {
    $_SERVER['REQUEST_URI']= '/favicon.ico';
    Assert::equals('/favicon.ico', (new SAPI())->uri());
  }

  #[Test]
  public function version() {
    $_SERVER['SERVER_PROTOCOL']= 'HTTP/1.1';
    Assert::equals('1.1', (new SAPI())->version());
  }

  #[Test]
  public function streamed_payload() {
    $_SERVER['HTTP_TRANSFER_ENCODING']= 'chunked';
    $_SERVER['REMOTE_ADDR']= '127.0.0.1';

    $fixture= new SAPI();
    iterator_count($fixture->headers());
    Assert::instance(ReadStream::class, $fixture->incoming());
  }

  #[Test]
  public function payload_with_length() {
    $_SERVER['CONTENT_LENGTH']= '4';
    $_SERVER['REMOTE_ADDR']= '127.0.0.1';

    $fixture= new SAPI();
    iterator_count($fixture->headers());
    Assert::instance(ReadLength::class, $fixture->incoming());
  }

  #[Test]
  public function parts_without_files() {
    $_FILES= [];
    Assert::equals([], iterator_to_array((new SAPI())->parts(self::BOUNDARY)));
  }

  #[Test]
  public function successful_upload() {
    Assert::equals(['file' => 'xp.web.Upload'], array_map(
      function($part) { return nameof($part); },
      $this->parts($this->upload('test.txt', 'text/plain'))
    ));
  }

  #[Test]
  public function successful_upload_with_array_parameter() {
    Assert::equals(['file[]' => 'xp.web.Upload'], array_map(
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

  #[Test]
  public function upload_name() {
    $parts= $this->parts($this->upload('test.txt', 'text/plain'));
    Assert::equals('test.txt', $parts['file']->name());
  }

  #[Test]
  public function upload_type() {
    $parts= $this->parts($this->upload('test.txt', 'text/plain'));
    Assert::equals('text/plain', $parts['file']->type());
  }

  #[Test]
  public function read_part() {
    $parts= $this->parts($this->upload('test.txt', 'text/plain'));
    Assert::equals('Test', $parts['file']->bytes());
  }

  #[Test]
  public function use_part_as_stream() {
    $parts= $this->parts($this->upload('test.txt', 'text/plain'));
    Assert::equals('Test', Streams::readAll($parts['file']));
  }

  #[Test]
  public function upload_exceeding_ini_size() {
    Assert::equals(['file' => 'web.io.Incomplete("test.txt", error= ERR_INI_SIZE)'], array_map(
      function($part) { return $part->toString(); },
      $this->parts($this->incomplete('test.txt', UPLOAD_ERR_INI_SIZE))
    ));
  }

  #[Test]
  public function upload_without_file() {
    Assert::equals(['file' => 'web.io.Incomplete("", error= ERR_NO_FILE)'], array_map(
      function($part) { return $part->toString(); },
      $this->parts($this->incomplete('', UPLOAD_ERR_NO_FILE))
    ));
  }

  #[Test, Expect(OperationNotSupportedException::class)]
  public function read_from_incomplete_upload() {
    $parts= $this->parts($this->incomplete('test.txt', UPLOAD_ERR_PARTIAL));
    $parts['file']->bytes();
  }

  #[Test, Expect(OperationNotSupportedException::class)]
  public function stream_incomplete_upload() {
    $parts= $this->parts($this->incomplete('test.txt', UPLOAD_ERR_PARTIAL));
    Streams::readAll($parts['file']);
  }

  #[Test]
  public function parameters_yielded_by_parts() {
    $_REQUEST= ['submit' => 'Test'];
    Assert::equals(['submit' => 'web.io.Param', 'file' => 'xp.web.Upload'], array_map(
      function($part) { return nameof($part); },
      $this->parts($this->upload('test.txt', 'text/plain'))
    ));
  }

  #[Test]
  public function parameter_unnecessary_urlencode_regression() {
    $_REQUEST= ['varname' => 'the value'];
    $fixture= new SAPI();
    $parts = iterator_to_array($fixture->parts(''));
    Assert::equals('the value', $parts['varname']->value());
  }
}