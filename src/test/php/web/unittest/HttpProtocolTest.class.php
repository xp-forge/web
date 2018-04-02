<?php namespace web\unittest;

use xp\web\srv\HttpProtocol;
use web\Application;
use web\Environment;
use unittest\TestCase;

class HttpProtocolTest extends TestCase {
  private $log;

  /** @return void */
  public function setUp() {
    $this->log= function($req, $res, $error= null) { };
  }

  private function application($handler) {
    return newinstance(Application::class, [new Environment('test', '.', '.', [])], [
      'routes' => function() use($handler) {
        return ['/' => $handler];
      }
    ]);
  }

  #[@test]
  public function can_create() {
    new HttpProtocol($this->application(function($req, $res) { }), $this->log);
  }

  #[@test]
  public function default_headers() {
    $p= new HttpProtocol($this->application(function($req, $res) { }), $this->log);

    $c= new Channel(["GET / HTTP/1.1\r\n\r\n"]);
    $p->handleData($c);

    $this->assertEquals(
      "HTTP/1.1 200 OK\r\n".
      "Date: ".gmdate('D, d M Y H:i:s T')."\r\n".
      "Content-Length: 0\r\n".
      "\r\n",
      implode('', $c->out)
    );
  }

  #[@test, @values(['localhost', 'localhost:8080'])]
  public function host_header_is_echoed($host) {
    $p= new HttpProtocol($this->application(function($req, $res) { }), $this->log);

    $c= new Channel(["GET / HTTP/1.1\r\nHost: ".$host."\r\n\r\n"]);
    $p->handleData($c);

    $this->assertEquals(
      "HTTP/1.1 200 OK\r\n".
      "Date: ".gmdate('D, d M Y H:i:s T')."\r\n".
      "Host: ".$host."\r\n".
      "Content-Length: 0\r\n".
      "\r\n",
      implode('', $c->out)
    );
  }

  #[@test]
  public function connection_close_is_honoured() {
    $p= new HttpProtocol($this->application(function($req, $res) { }), $this->log);

    $c= new Channel(["GET / HTTP/1.1\r\nConnection: close\r\n\r\n"]);
    $p->handleData($c);

    $this->assertEquals(
      "HTTP/1.1 200 OK\r\n".
      "Date: ".gmdate('D, d M Y H:i:s T')."\r\n".
      "Connection: close\r\n".
      "Content-Length: 0\r\n".
      "\r\n",
      implode('', $c->out)
    );
  }

  #[@test]
  public function responds_with_http_10_for_http_10_requests() {
    $p= new HttpProtocol($this->application(function($req, $res) { }), $this->log);

    $c= new Channel(["GET / HTTP/1.0\r\n\r\n"]);
    $p->handleData($c);

    $this->assertEquals(
      "HTTP/1.0 200 OK\r\n".
      "Date: ".gmdate('D, d M Y H:i:s T')."\r\n".
      "Content-Length: 0\r\n".
      "\r\n",
      implode('', $c->out)
    );
  }
}