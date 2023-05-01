<?php namespace web\unittest\server;

use io\streams\Streams;
use peer\{Socket, SocketEndpoint};
use test\{Assert, Test, Values};
use xp\web\srv\Input;

class InputTest {

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

      public function read($maxLen= 4096) {
        $read= false === ($p= strpos($this->bytes, "\n")) ? $maxLen : $p + 1;
        $chunk= substr($this->bytes, 0, $read);
        $this->bytes= substr($this->bytes, $read);
        return (string)$chunk;
      }

      public function readBinary($maxLen= 4096) {
        $chunk= substr($this->bytes, 0, $maxLen);
        $this->bytes= substr($this->bytes, $maxLen);
        return (string)$chunk;
      }
    };
  }

  #[Test]
  public function can_create() {
    new Input($this->socket("GET / HTTP/1.1\r\n\r\n"));
  }

  #[Test]
  public function close_kind() {
    Assert::equals(Input::CLOSE, (new Input($this->socket('')))->kind);
  }

  #[Test]
  public function request_kind() {
    Assert::equals(Input::REQUEST, (new Input($this->socket("GET / HTTP/1.1\r\n\r\n")))->kind);
  }

  #[Test]
  public function malformed_kind() {
    Assert::equals('EHLO example.org', (new Input($this->socket("EHLO example.org\r\n")))->kind);
  }

  #[Test]
  public function http_scheme_default() {
    Assert::equals('http', (new Input($this->socket("GET / HTTP/1.1\r\n\r\n")))->scheme());
  }

  #[Test]
  public function method() {
    Assert::equals('GET', (new Input($this->socket("GET / HTTP/1.1\r\n\r\n")))->method());
  }

  #[Test]
  public function uri() {
    Assert::equals('/', (new Input($this->socket("GET / HTTP/1.1\r\n\r\n")))->uri());
  }

  #[Test]
  public function version() {
    Assert::equals('1.1', (new Input($this->socket("GET / HTTP/1.1\r\n\r\n")))->version());
  }

  #[Test]
  public function no_headers() {
    $input= new Input($this->socket("GET / HTTP/1.1\r\n\r\n"));
    Assert::equals(
      ['Remote-Addr' => '127.0.0.1'],
      iterator_to_array($input->headers())
    );
  }

  #[Test]
  public function headers() {
    $input= new Input($this->socket("GET / HTTP/1.1\r\nHost: example\r\nDate: Tue, 15 Nov 1994 08:12:31 GMT\r\n\r\n"));
    Assert::equals(
      ['Remote-Addr' => '127.0.0.1', 'Host' => 'example', 'Date' => 'Tue, 15 Nov 1994 08:12:31 GMT'],
      iterator_to_array($input->headers())
    );
  }

  #[Test]
  public function without_payload() {
    $input= new Input($this->socket("GET / HTTP/1.1\r\n\r\n"));
    iterator_count($input->headers());

    Assert::null($input->incoming());
  }

  #[Test]
  public function with_content_length() {
    $input= new Input($this->socket("POST / HTTP/1.1\r\nContent-Length: 4\r\n\r\nTest"));
    iterator_count($input->headers());

    Assert::equals('Test', Streams::readAll($input->incoming()));
  }

  #[Test]
  public function with_chunked_te() {
    $input= new Input($this->socket("POST / HTTP/1.1\r\nTransfer-Encoding: chunked\r\n\r\n4\r\nTest\r\n0\r\n\r\n"));
    iterator_count($input->headers());

    Assert::equals('Test', Streams::readAll($input->incoming()));
  }

  #[Test]
  public function read_length() {
    $input= new Input($this->socket("POST / HTTP/1.1\r\nContent-Length: 4\r\n\r\nTest"));
    iterator_count($input->headers());

    Assert::equals('Test', $input->read(4));
  }

  #[Test]
  public function read_all() {
    $input= new Input($this->socket("POST / HTTP/1.1\r\nContent-Length: 4\r\n\r\nTest"));
    iterator_count($input->headers());

    Assert::equals('Test', $input->read(-1));
  }

  #[Test, Values([1024, 4096, 8192])]
  public function with_large_headers($length) {
    $header= 'cookie='.str_repeat('*', $length);
    $input= new Input($this->socket("GET / HTTP/1.1\r\nCookie: {$header}\r\n\r\n"));
    $headers= iterator_to_array($input->headers());

    Assert::equals($header, $headers['Cookie']);
  }
}