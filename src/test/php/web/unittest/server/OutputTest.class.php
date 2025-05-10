<?php namespace web\unittest\server;

use peer\{Socket, SocketException};
use test\{Assert, Test, Values};
use web\io\{Buffered, WriteChunks};
use xp\web\srv\{Output, CannotWrite};

class OutputTest {

  /**
   * Returns a socket which can be written to and read from
   *
   * @return peer.Socket
   */
  private function socket() {
    return new class() extends Socket {
      private $bytes= '';

      public function __construct() { }

      public function readBinary($maxLen= 4096) {
        $chunk= substr($this->bytes, 0, $maxLen);
        $this->bytes= substr($this->bytes, $maxLen);
        return (string)$chunk;
      }

      public function write($bytes) {
        $this->bytes.= $bytes;
      }
    };
  }

  /**
   * Returns a socket which raises an error when being written to
   *
   * @param  string $error
   * @return peer.Socket
   */
  private function throws($error) {
    return new class($error) extends Socket {
      private $error;

      public function __construct($error) { $this->error= $error; }

      public function write($bytes) {
        throw new SocketException($this->error);
      }
    };
  }

  #[Test]
  public function can_create() {
    new Output($this->socket());
  }

  #[Test]
  public function chunked_stream_for_http_1_1() {
    Assert::instance(WriteChunks::class, (new Output($this->socket(), '1.1'))->stream());
  }

  #[Test]
  public function buffered_stream_for_http_1_0() {
    Assert::instance(Buffered::class, (new Output($this->socket(), '1.0'))->stream());
  }

  #[Test, Values(['1.0', '1.1'])]
  public function begin($version) {
    $socket= $this->socket();
    (new Output($socket, $version))->begin(200, 'OK', ['Content-Length' => [0]]);
    Assert::equals("HTTP/{$version} 200 OK\r\nContent-Length: 0\r\n\r\n", $socket->readBinary());
  }

  #[Test]
  public function write() {
    $socket= $this->socket();
    (new Output($socket))->write('Test');
    Assert::equals('Test', $socket->readBinary());
  }

  #[Test]
  public function begin_error() {
    Assert::throws(CannotWrite::class, function() {
      (new Output($this->throws('Write failed')))->begin(200, 'OK', []);
    });
  }

  #[Test]
  public function write_error() {
    Assert::throws(CannotWrite::class, function() {
      (new Output($this->throws('Write failed')))->write('Test');
    });
  }
}