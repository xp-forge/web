<?php namespace web\unittest;

use test\{Assert, Test, Values};
use web\io\{TestInput, TestOutput};
use web\logging\{ToAllOf, ToConsole, ToFunction};
use web\{Error, Logging, Request, Response};

class LoggingTest {

  /** @return iterable */
  private function arguments() {
    yield ['GET /', null];
    yield ['GET / Test', new Error(404, 'Test')];
  }

  #[Test]
  public function can_create() {
    new Logging(null);
  }

  #[Test]
  public function can_create_with_sink() {
    new Logging(new ToFunction(function($req, $res, $error) { }));
  }

  #[Test]
  public function target() {
    $sink= new ToFunction(function($req, $res, $error) { });
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
  public function log($expected, $error) {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $logged= [];
    $log= new Logging(new ToFunction(function($req, $res, $error) use(&$logged) {
      $logged[]= $req->method().' '.$req->uri()->path().($error ? ' '.$error->getMessage() : '');
    }));
    $log->log($req, $res, $error);

    Assert::equals([$expected], $logged);
  }

  #[Test]
  public function pipe() {
    $a= new ToFunction(function($req, $res, $error) { /* a */ });
    $b= new ToFunction(function($req, $res, $error) { /* b */ });
    Assert::equals($b, (new Logging($a))->pipe($b)->sink());
  }

  #[Test]
  public function pipe_with_string_arg() {
    Assert::equals(new ToConsole(), (new Logging())->pipe('-')->sink());
  }

  #[Test]
  public function tee() {
    $a= new ToFunction(function($req, $res, $error) { /* a */ });
    $b= new ToFunction(function($req, $res, $error) { /* b */ });
    Assert::equals(new ToAllOf($a, $b), (new Logging($a))->tee($b)->sink());
  }

  #[Test]
  public function tee_multiple() {
    $a= new ToFunction(function($req, $res, $error) { /* a */ });
    $b= new ToFunction(function($req, $res, $error) { /* b */ });
    $c= new ToFunction(function($req, $res, $error) { /* c */ });
    Assert::equals(new ToAllOf($a, $b, $c), (new Logging($a))->tee($b)->tee($c)->sink());
  }

  #[Test]
  public function pipe_on_no_logging() {
    $sink= new ToFunction(function($req, $res, $error) { });
    Assert::equals($sink, (new Logging(null))->pipe($sink)->sink());
  }

  #[Test]
  public function tee_on_no_logging() {
    $sink= new ToFunction(function($req, $res, $error) { });
    Assert::equals($sink, (new Logging(null))->tee($sink)->sink());
  }
}