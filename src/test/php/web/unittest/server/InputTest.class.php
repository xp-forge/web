<?php namespace web\unittest\server;

use io\streams\Streams;
use peer\{Socket, SocketEndpoint};
use unittest\TestCase;
use xp\web\srv\Input;

class InputTest extends TestCase {

  /**
   * Returns a socket which can be read from
   *
   * @param  string $bytes
   * @return peer.Socket
   */
  private function socket($bytes) {
    return new class($bytes) extends Socket {
      private $bytes;

      public function __construct($bytes) { $this->bytes= $bytes; }

      public function remoteEndpoint() { return new SocketEndpoint('127.0.0.1', 61000); }

      public function eof() { return 0 === strlen($this->bytes); }

      public function canRead($timeout= null) { return strlen($this->bytes) > 0; }

      public function readBinary($maxLen= 4096) {
        $chunk= substr($this->bytes, 0, $maxLen);
        $this->bytes= substr($this->bytes, $maxLen);
        return (string)$chunk;
      }
    };
  }

  #[@test]
  public function can_create() {
    new Input($this->socket("GET / HTTP/1.1\r\n\r\n"));
  }

  #[@test]
  public function close_kind() {
    $this->assertEquals(Input::CLOSE, (new Input($this->socket('')))->kind);
  }

  #[@test]
  public function request_kind() {
    $this->assertEquals(Input::REQUEST, (new Input($this->socket("GET / HTTP/1.1\r\n\r\n")))->kind);
  }

  #[@test]
  public function malformed_kind() {
    $this->assertEquals('EHLO example.org', (new Input($this->socket("EHLO example.org\r\n")))->kind);
  }

  #[@test]
  public function http_scheme_default() {
    $this->assertEquals('http', (new Input($this->socket("GET / HTTP/1.1\r\n\r\n")))->scheme());
  }

  #[@test]
  public function method() {
    $this->assertEquals('GET', (new Input($this->socket("GET / HTTP/1.1\r\n\r\n")))->method());
  }

  #[@test]
  public function uri() {
    $this->assertEquals('/', (new Input($this->socket("GET / HTTP/1.1\r\n\r\n")))->uri());
  }

  #[@test]
  public function version() {
    $this->assertEquals('1.1', (new Input($this->socket("GET / HTTP/1.1\r\n\r\n")))->version());
  }

  #[@test]
  public function no_headers() {
    $input= new Input($this->socket("GET / HTTP/1.1\r\n\r\n"));
    $this->assertEquals(
      ['Remote-Addr' => '127.0.0.1'],
      iterator_to_array($input->headers())
    );
  }

  #[@test]
  public function headers() {
    $input= new Input($this->socket("GET / HTTP/1.1\r\nHost: example\r\nDate: Tue, 15 Nov 1994 08:12:31 GMT\r\n\r\n"));
    $this->assertEquals(
      ['Remote-Addr' => '127.0.0.1', 'Host' => 'example', 'Date' => 'Tue, 15 Nov 1994 08:12:31 GMT'],
      iterator_to_array($input->headers())
    );
  }

  #[@test]
  public function without_payload() {
    $input= new Input($this->socket("GET / HTTP/1.1\r\n\r\n"));
    iterator_count($input->headers());

    $this->assertNull($input->incoming());
  }

  #[@test]
  public function with_content_length() {
    $input= new Input($this->socket("POST / HTTP/1.1\r\nContent-Length: 4\r\n\r\nTest"));
    iterator_count($input->headers());

    $this->assertEquals('Test', Streams::readAll($input->incoming()));
  }

  #[@test]
  public function with_chunked_te() {
    $input= new Input($this->socket("POST / HTTP/1.1\r\nTransfer-Encoding: chunked\r\n\r\n4\r\nTest\r\n0\r\n\r\n"));
    iterator_count($input->headers());

    $this->assertEquals('Test', Streams::readAll($input->incoming()));
  }

  #[@test]
  public function read_length() {
    $input= new Input($this->socket("POST / HTTP/1.1\r\nContent-Length: 4\r\n\r\nTest"));
    iterator_count($input->headers());

    $this->assertEquals('Test', $input->read(4));
  }

  #[@test]
  public function read_all() {
    $input= new Input($this->socket("POST / HTTP/1.1\r\nContent-Length: 4\r\n\r\nTest"));
    iterator_count($input->headers());

    $this->assertEquals('Test', $input->read(-1));
  }
}