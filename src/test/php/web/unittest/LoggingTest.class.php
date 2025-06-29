<?php namespace web\unittest;

use test\{Assert, Before, Test, Values};
use web\io\{TestInput, TestOutput};
use web\logging\{ToAllOf, ToConsole, ToFunction};
use web\{Error, Logging, Request, Response};

class LoggingTest {
  private $noop;

  /** @return iterable */
  private function arguments() {
    yield ['GET /', []];
    yield ['GET / Test', ['error' => new Error(404, 'Test')]];
  }

  #[Before]
  public function noop() {
    $this->noop= function($status, $method, $uri, $hints) { };
  }

  #[Test]
  public function can_create() {
    new Logging(null);
  }

  #[Test]
  public function can_create_with_sink() {
    new Logging(new ToFunction($this->noop));
  }

  #[Test]
  public function target() {
    $sink= new ToFunction($this->noop);
    Assert::equals($sink->target(), (new Logging($sink))->target());
  }

  #[Test]
  public function no_logging_target() {
    Assert::equals('(no logging)', (new Logging(null))->target());
  }

  #[Test, Values([null, ''])]
  public function no_logging_target_of($target) {
    Assert::equals('(no logging)', Logging::of($target)->target());
  }

  #[Test, Values(from: 'arguments')]
  public function log($expected, $hints) {
    $logged= [];
    $log= new Logging(new ToFunction(function($status, $method, $uri, $hints) use(&$logged) {
      $logged[]= $method.' '.$uri.($hints ? ' '.$hints['error']->getMessage() : '');
    }));
    $log->log(200, 'GET', '/', $hints);

    Assert::equals([$expected], $logged);
  }

  #[Test, Values(from: 'arguments')]
  public function exchange($expected, $hints) {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $logged= [];
    $log= new Logging(new ToFunction(function($status, $method, $uri, $hints) use(&$logged) {
      $logged[]= $method.' '.$uri.($hints ? ' '.$hints['error']->getMessage() : '');
    }));
    $log->exchange($req, $res, $hints);

    Assert::equals([$expected], $logged);
  }

  #[Test]
  public function pipe() {
    $a= new ToFunction($this->noop);
    $b= new ToFunction($this->noop);
    Assert::equals($b, (new Logging($a))->pipe($b)->sink());
  }

  #[Test]
  public function pipe_with_string_arg() {
    Assert::equals(new ToConsole(), (new Logging())->pipe('-')->sink());
  }

  #[Test]
  public function tee() {
    $a= new ToFunction($this->noop);
    $b= new ToFunction($this->noop);
    Assert::equals(new ToAllOf($a, $b), (new Logging($a))->tee($b)->sink());
  }

  #[Test]
  public function tee_multiple() {
    $a= new ToFunction($this->noop);
    $b= new ToFunction($this->noop);
    $c= new ToFunction($this->noop);
    Assert::equals(new ToAllOf($a, $b, $c), (new Logging($a))->tee($b)->tee($c)->sink());
  }

  #[Test]
  public function pipe_on_no_logging() {
    $sink= new ToFunction($this->noop);
    Assert::equals($sink, (new Logging(null))->pipe($sink)->sink());
  }

  #[Test]
  public function tee_on_no_logging() {
    $sink= new ToFunction($this->noop);
    Assert::equals($sink, (new Logging(null))->tee($sink)->sink());
  }
}