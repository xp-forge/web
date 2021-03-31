<?php namespace web\unittest;

use unittest\{Assert, Test};
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class AsyncTest {

  /**
   * Assertion helper
   *
   * @param  string $expected
   * @param  web.Response $response
   * @throws unittest.AssertionFailedError
   */
  private function assertResponse($expected, $response) {
    Assert::equals($expected, $response->output()->bytes());
  }

  /**
   * Invoke given handler and return response
   *
   * @param  web.Handler $handler
   * @return web.Response
   */
  private function handle($handler) {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    try {
      foreach ($handler($req, $res) ?? [] as $_) { }
      return $res;
    } finally {
      $res->end();
    }
  }

  #[Test]
  public function synchronous_variant() {
    $handler= function($req, $res) {
      $res->answer(200);
      $res->send('Hello', 'text/plain');
    };

    $res= $this->handle($handler);
    $this->assertResponse("HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nContent-Length: 5\r\n\r\nHello", $res);
  }

  #[Test]
  public function with_yield() {
    $handler= function($req, $res) {
      $res->answer(200);
      yield;
      $res->send('Hello', 'text/plain');
    };

    $res= $this->handle($handler);
    $this->assertResponse("HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nContent-Length: 5\r\n\r\nHello", $res);
  }

  #[Test]
  public function write_to_stream_yielding() {
    $handler= function($req, $res) {
      $res->answer(200);

      $out= $res->stream();
      for ($i= 0; $i < 10; $i++) {
        $out->write($i);
        yield;
      }
      $out->close();
    };

    $res= $this->handle($handler);
    $this->assertResponse("HTTP/1.1 200 OK\r\nTransfer-Encoding: chunked\r\n\r\na\r\n0123456789\r\n0\r\n\r\n", $res);
  }
}