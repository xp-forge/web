<?php namespace web\unittest;

use io\streams\Streams;
use unittest\{Test, TestCase, Values};
use web\{Application, Environment, Logging};
use xp\web\srv\HttpProtocol;

class HttpProtocolTest extends TestCase {
  private $log;

  /** @return void */
  public function setUp() {
    $this->log= new Logging(null);
  }

  private function application($handler) {
    return newinstance(Application::class, [new Environment('test', '.', '.', [])], [
      'routes' => function() use($handler) {
        return ['/' => $handler];
      }
    ]);
  }

  /**
   * Assertion helper
   *
   * @param  string $expected Regular expression without delimiters
   * @param  string[] $out
   * @throws unittest.AssertionFailedError
   */
  private function assertHttp($expected, $out) {
    $actual= implode('', $out);
    if (!preg_match('#^'.$expected.'$#', $actual)) {
      $this->fail('=~', $actual, $expected);
    }
  }

  #[Test]
  public function can_create() {
    new HttpProtocol($this->application(function($req, $res) { }), $this->log);
  }

  #[Test]
  public function default_headers() {
    $p= new HttpProtocol($this->application(function($req, $res) { }), $this->log);

    $c= new Channel(["GET / HTTP/1.1\r\n\r\n"]);
    $p->handleData($c);

    $this->assertHttp(
      "HTTP/1.1 200 OK\r\n".
      "Date: [A-Za-z]+, [0-9]+ [A-Za-z]+ [0-9]+ [0-9]+:[0-9]+:[0-9]+ GMT\r\n".
      "Content-Length: 0\r\n".
      "\r\n",
      $c->out
    );
  }

  #[Test, Values(['localhost', 'localhost:8080'])]
  public function host_header_is_echoed($host) {
    $p= new HttpProtocol($this->application(function($req, $res) { }), $this->log);

    $c= new Channel(["GET / HTTP/1.1\r\nHost: ".$host."\r\n\r\n"]);
    $p->handleData($c);

    $this->assertHttp(
      "HTTP/1.1 200 OK\r\n".
      "Date: [A-Za-z]+, [0-9]+ [A-Za-z]+ [0-9]+ [0-9]+:[0-9]+:[0-9]+ GMT\r\n".
      "Host: ".$host."\r\n".
      "Content-Length: 0\r\n".
      "\r\n",
      $c->out
    );
  }

  #[Test]
  public function connection_close_is_honoured() {
    $p= new HttpProtocol($this->application(function($req, $res) { }), $this->log);

    $c= new Channel(["GET / HTTP/1.1\r\nConnection: close\r\n\r\n"]);
    $p->handleData($c);

    $this->assertHttp(
      "HTTP/1.1 200 OK\r\n".
      "Date: [A-Za-z]+, [0-9]+ [A-Za-z]+ [0-9]+ [0-9]+:[0-9]+:[0-9]+ GMT\r\n".
      "Connection: close\r\n".
      "Content-Length: 0\r\n".
      "\r\n",
      $c->out
    );
  }

  #[Test]
  public function responds_with_http_10_for_http_10_requests() {
    $p= new HttpProtocol($this->application(function($req, $res) { }), $this->log);

    $c= new Channel(["GET / HTTP/1.0\r\n\r\n"]);
    $p->handleData($c);

    $this->assertHttp(
      "HTTP/1.0 200 OK\r\n".
      "Date: [A-Za-z]+, [0-9]+ [A-Za-z]+ [0-9]+ [0-9]+:[0-9]+:[0-9]+ GMT\r\n".
      "Content-Length: 0\r\n".
      "\r\n",
      $c->out
    );
  }

  #[Test]
  public function handles_chunked_transfer_input() {
    $echo= function($req, $res) { $res->send(Streams::readAll($req->stream()), 'text/plain'); };
    $p= new HttpProtocol($this->application($echo), $this->log);

    $c= new Channel([
      "POST / HTTP/1.1\r\nContent-Type: text/plain\r\nTransfer-Encoding: chunked\r\n\r\n",
      "4\r\nTest\r\n0\r\n\r\n"
    ]);
    $p->handleData($c);

    $this->assertHttp(
      "HTTP/1.1 200 OK\r\n".
      "Date: [A-Za-z]+, [0-9]+ [A-Za-z]+ [0-9]+ [0-9]+:[0-9]+:[0-9]+ GMT\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 4\r\n".
      "\r\nTest",
      $c->out
    );
  }

  #[Test]
  public function buffers_and_sets_content_length_for_http10() {
    $echo= function($req, $res) { with ($res->stream(), function($s) { $s->write('Test'); }); };
    $p= new HttpProtocol($this->application($echo), $this->log);

    $c= new Channel(["GET / HTTP/1.0\r\n\r\n"]);
    $p->handleData($c);

    $this->assertHttp(
      "HTTP/1.0 200 OK\r\n".
      "Date: [A-Za-z]+, [0-9]+ [A-Za-z]+ [0-9]+ [0-9]+:[0-9]+:[0-9]+ GMT\r\n".
      "Content-Length: 4\r\n".
      "\r\nTest",
      $c->out
    );
  }

  #[Test]
  public function produces_chunked_transfer_output_for_http11() {
    $echo= function($req, $res) { with ($res->stream(), function($s) { $s->write('Test'); }); };
    $p= new HttpProtocol($this->application($echo), $this->log);

    $c= new Channel(["GET / HTTP/1.1\r\n\r\n"]);
    $p->handleData($c);

    $this->assertHttp(
      "HTTP/1.1 200 OK\r\n".
      "Date: [A-Za-z]+, [0-9]+ [A-Za-z]+ [0-9]+ [0-9]+:[0-9]+:[0-9]+ GMT\r\n".
      "Transfer-Encoding: chunked\r\n".
      "\r\n4\r\nTest\r\n0\r\n\r\n",
      $c->out
    );
  }
}