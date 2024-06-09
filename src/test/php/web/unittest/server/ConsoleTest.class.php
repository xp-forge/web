<?php namespace web\unittest\server;

use lang\IllegalArgumentException;
use test\{Assert, Test, Values};
use web\io\{TestInput, TestOutput};
use web\{Error, Filters, Request, Response};
use xp\web\dev\Console;

class ConsoleTest {

  /**
   * Handles a request with the given headers and handler function,
   * returning the response.
   *
   * @param  function(web.Request, web.Response): var $handler
   * @return web.Response
   */
  private function handle($handler) {
    $compress= new Filters([new Console()], $handler);
    $req= new Request(new TestInput('GET', '/?test=true'));
    $res= new Response(new TestOutput());

    foreach ($compress->handle($req, $res) ?? [] as $_) { }
    return $res;
  }

  #[Test]
  public function can_create() {
    new Console();
  }

  #[Test]
  public function send() {
    $res= $this->handle(function($req, $res) {
      $res->send('Test', 'text/plain; charset=utf-8');
    });

    Assert::equals(
      "HTTP/1.1 200 OK\r\n".
      "Content-Type: text/plain; charset=utf-8\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Test",
      $res->output()->bytes()
    );
  }

  #[Test]
  public function stream() {
    $res= $this->handle(function($req, $res) {
      $res->header('Content-Type', 'text/plain; charset=utf-8');
      $stream= $res->stream();
      $stream->write('Test');
      $stream->close();
    });

    Assert::equals(
      "HTTP/1.1 200 OK\r\n".
      "Content-Type: text/plain; charset=utf-8\r\n".
      "Transfer-Encoding: chunked\r\n".
      "\r\n".
      "4\r\nTest\r\n0\r\n\r\n",
      $res->output()->bytes()
    );
  }

  #[Test]
  public function empty_echo_does_not_trigger() {
    $res= $this->handle(function($req, $res) {
      echo '';
      $res->send('Test', 'text/plain; charset=utf-8');
    });

    Assert::equals(
      "HTTP/1.1 200 OK\r\n".
      "Content-Type: text/plain; charset=utf-8\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Test",
      $res->output()->bytes()
    );
  }

  #[Test, Values(['true', '0', 0, 0.0])]
  public function echo_output_appears_in_console($arg) {
    $res= $this->handle(function($req, $res) use($arg) {
      echo $arg;
      $res->send('Test', 'text/plain; charset=utf-8');
    });

    Assert::matches(
      '/<pre id="output">'.(string)$arg.'<\/pre>/',
      $res->output()->bytes()
    );
  }

  #[Test]
  public function var_dump_output_appears_in_console() {
    $res= $this->handle(function($req, $res) {
      var_dump($req->param('test'));
      $res->send('Test', 'text/plain; charset=utf-8');
    });

    Assert::matches(
      '/<pre id="output">.*string\(4\) &quot;true&quot;\n<\/pre>/s',
      $res->output()->bytes()
    );
  }

  #[Test]
  public function status_appears_in_console() {
    $res= $this->handle(function($req, $res) {
      echo 'Test';
      $res->answer(204);
    });

    Assert::matches(
      '/HTTP\/1.1 <span id="status">204 No Content<\/span>/',
      $res->output()->bytes()
    );
  }

  #[Test]
  public function headers_appear_in_console() {
    $res= $this->handle(function($req, $res) {
      echo $req->param('test');
      $res->send('Test', 'text/plain; charset=utf-8');
    });

    Assert::matches(
      '/<td class="name">Content-Type<\/td>.*<td class="value">text\/plain; charset=utf-8<\/td>/s',
      $res->output()->bytes()
    );
  }

  #[Test]
  public function uncaught_exceptions() {
    $res= $this->handle(function($req, $res) {
      throw new IllegalArgumentException('Test');
    });

    Assert::matches(
      '/HTTP\/1.1 <span id="status">500 Internal Server Error<\/span>/',
      $res->output()->bytes()
    );
    Assert::matches(
      '/Exception lang.IllegalArgumentException \(Test\)/',
      $res->output()->bytes()
    );
  }

  #[Test]
  public function uncaught_errors() {
    $res= $this->handle(function($req, $res) {
      throw new Error(404);
    });

    Assert::matches(
      '/HTTP\/1.1 <span id="status">404 Not Found<\/span>/',
      $res->output()->bytes()
    );
    Assert::matches(
      '/Error web.Error\(#404: Not Found\)/',
      $res->output()->bytes()
    );
  }
}