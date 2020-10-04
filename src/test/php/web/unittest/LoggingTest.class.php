<?php namespace web\unittest;

use unittest\{Test, TestCase, Values};
use web\io\{TestInput, TestOutput};
use web\logging\{ToAllOf, ToFunction};
use web\{Error, Logging, Request, Response};

class LoggingTest extends TestCase {

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
    $this->assertEquals($sink->target(), (new Logging($sink))->target());
  }

  #[Test]
  public function no_logging_target() {
    $this->assertEquals('(no logging)', (new Logging(null))->target());
  }

  #[Test, Values('arguments')]
  public function log($expected, $error) {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $logged= [];
    $log= new Logging(new ToFunction(function($req, $res, $error) use(&$logged) {
      $logged[]= $req->method().' '.$req->uri()->path().($error ? ' '.$error->getMessage() : '');
    }));
    $log->log($req, $res, $error);

    $this->assertEquals([$expected], $logged);
  }

  #[Test]
  public function pipe() {
    $a= new ToFunction(function($req, $res, $error) { /* a */ });
    $b= new ToFunction(function($req, $res, $error) { /* b */ });
    $this->assertEquals($b, (new Logging($a))->pipe($b)->sink());
  }

  #[Test]
  public function tee() {
    $a= new ToFunction(function($req, $res, $error) { /* a */ });
    $b= new ToFunction(function($req, $res, $error) { /* b */ });
    $this->assertEquals(new ToAllOf($a, $b), (new Logging($a))->tee($b)->sink());
  }

  #[Test]
  public function tee_multiple() {
    $a= new ToFunction(function($req, $res, $error) { /* a */ });
    $b= new ToFunction(function($req, $res, $error) { /* b */ });
    $c= new ToFunction(function($req, $res, $error) { /* c */ });
    $this->assertEquals(new ToAllOf($a, $b, $c), (new Logging($a))->tee($b)->tee($c)->sink());
  }

  #[Test]
  public function pipe_on_no_logging() {
    $sink= new ToFunction(function($req, $res, $error) { });
    $this->assertEquals($sink, (new Logging(null))->pipe($sink)->sink());
  }

  #[Test]
  public function tee_on_no_logging() {
    $sink= new ToFunction(function($req, $res, $error) { });
    $this->assertEquals($sink, (new Logging(null))->tee($sink)->sink());
  }
}