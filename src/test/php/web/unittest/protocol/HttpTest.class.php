<?php namespace web\unittest\protocol;

use io\streams\Streams;
use lang\IllegalStateException;
use peer\SocketTimeoutException;
use unittest\TestCase;
use web\Application;
use web\Environment;
use web\Error;
use web\Logging;
use web\protocol\Http;
use web\unittest\Channel;

class HttpTest extends TestCase {
  private $log;

  /** @return void */
  public function setUp() {
    $this->log= new Logging(null);
    putenv('NO_KEEPALIVE=');
  }

  /**
   * Creates a fixture
   *
   * @param  function(web.Request, web.Response): var $handler
   * @return web.protocol.Http
   */
  private function fixture($handler) {
    $application= newinstance(Application::class, [new Environment('test')], [
      'routes' => function() use($handler) {
        return ['/' => $handler];
      }
    ]);

    $p= new Http($application, $this->log);
    $p->initialize();
    return $p;
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
    if (!preg_match('#^'.$expected.'$#m', $actual)) {
      $this->fail('=~', $actual, $expected);
    }
  }

  #[@test]
  public function can_create() {
    $this->fixture(function($req, $res) { });
  }

  #[@test]
  public function initialize() {
    $p= $this->fixture(function($req, $res) { });
    $p->initialize();
  }

  #[@test]
  public function handle_connect_itself_is_noop() {
    $p= $this->fixture(function($req, $res) { });

    $c= new Channel([]);
    $p->handleConnect($c);
    $this->assertFalse($c->closed);
  }

  #[@test]
  public function handle_disconnect_closes_socket() {
    $p= $this->fixture(function($req, $res) { });

    $c= new Channel([]);
    $p->handleDisconnect($c);
    $this->assertTrue($c->closed);
  }

  #[@test]
  public function handle_error_closes_socket() {
    $p= $this->fixture(function($req, $res) { });

    $c= new Channel([]);
    $p->handleError($c, new SocketTimeoutException('Test', 42.0));
    $this->assertTrue($c->closed);
  }

  #[@test]
  public function default_headers() {
    $p= $this->fixture(function($req, $res) { });

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

  #[@test, @values(['localhost', 'localhost:8080'])]
  public function host_header_is_echoed($host) {
    $p= $this->fixture(function($req, $res) { });

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

  #[@test]
  public function connection_close_is_honoured() {
    $p= $this->fixture(function($req, $res) { });

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

  #[@test]
  public function connection_close_if_used_with_no_keepalive() {
    putenv('NO_KEEPALIVE=true');
    $p= $this->fixture(function($req, $res) { });

    $c= new Channel(["GET / HTTP/1.1\r\n\r\n"]);
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

  #[@test]
  public function responds_with_http_10_for_http_10_requests() {
    $p= $this->fixture(function($req, $res) { });

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

  #[@test]
  public function handles_chunked_transfer_input() {
    $echo= function($req, $res) { $res->send(Streams::readAll($req->stream()), 'text/plain'); };
    $p= $this->fixture($echo);

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

  #[@test]
  public function buffers_and_sets_content_length_for_http10() {
    $echo= function($req, $res) { with ($res->stream(), function($s) { $s->write('Test'); }); };
    $p= $this->fixture($echo);

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

  #[@test]
  public function produces_chunked_transfer_output_for_http11() {
    $echo= function($req, $res) { with ($res->stream(), function($s) { $s->write('Test'); }); };
    $p= $this->fixture($echo);

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

  #[@test]
  public function handles_incomplete_request() {
    $p= $this->fixture(function($req, $res) { });

    $c= new Channel(['GET']);
    $p->handleData($c);

    $this->assertHttp(
      "HTTP/1.1 400 Bad Request\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 30\r\n".
      "Connection: close\r\n".
      "\r\n".
      "Incomplete HTTP request: \"GET\"",
      $c->out
    );
  }

  #[@test]
  public function handles_preflight_request() {
    $p= $this->fixture(function($req, $res) { });

    $c= new Channel(['']);
    $p->handleData($c);

    $this->assertEquals([], $c->out);
    $this->assertTrue($c->closed);
  }

  #[@test]
  public function yields_http_error() {
    $p= $this->fixture(function($req, $res) { throw new Error(404, 'Test'); });

    $c= new Channel(["GET / HTTP/1.1\r\n\r\n"]);
    $p->handleData($c);

    $this->assertHttp(
      "HTTP/1.1 404 Not Found\r\n".
      "Date: [A-Za-z]+, [0-9]+ [A-Za-z]+ [0-9]+ [0-9]+:[0-9]+:[0-9]+ GMT\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: [0-9]+\r\n".
      "\r\n".
      "<!DOCTYPE html>.*",
      $c->out
    );
  }

  #[@test]
  public function wraps_exceptions_in_internal_server_errors() {
    $p= $this->fixture(function($req, $res) { throw new IllegalStateException('Test'); });

    $c= new Channel(["GET / HTTP/1.1\r\n\r\n"]);
    $p->handleData($c);

    $this->assertHttp(
      "HTTP/1.1 500 Internal Server Error\r\n".
      "Date: [A-Za-z]+, [0-9]+ [A-Za-z]+ [0-9]+ [0-9]+:[0-9]+:[0-9]+ GMT\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: [0-9]+\r\n".
      "\r\n".
      "<!DOCTYPE html>.*",
      $c->out
    );
  }

  #[@test]
  public function prints_stacktrace_if_response_flushed() {
    $printed= false;
    $p= $this->fixture(function($req, $res) use(&$printed) {
      $res->answer(201);
      $res->flush();

      // After flushing, we raise an error
      throw newinstance(Error::class, [500, 'Test'], [
        'printStackTrace' => function($fd= STDERR) use(&$printed) {
          $printed= true;
        }
      ]);
    });

    $c= new Channel(["GET / HTTP/1.1\r\n\r\n"]);
    $p->handleData($c);

    $this->assertTrue($printed);
    $this->assertHttp(
      "HTTP/1.1 201 Created\r\n".
      "Date: [A-Za-z]+, [0-9]+ [A-Za-z]+ [0-9]+ [0-9]+:[0-9]+:[0-9]+ GMT\r\n".
      "\r\n",
      $c->out
    );
  }
}