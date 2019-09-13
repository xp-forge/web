<?php namespace web\unittest;

use web\Error;
use web\Logging;
use web\Request;
use web\Response;
use web\io\TestInput;
use web\io\TestOutput;
use web\logging\ToAllOf;
use web\logging\ToFunction;

class LoggingTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Logging(null);
  }

  #[@test]
  public function can_create_with_sink() {
    new Logging(new ToFunction(function($kind, $uri, $status, $error= null) { }));
  }

  #[@test]
  public function target() {
    $sink= new ToFunction(function($kind, $uri, $status, $error= null) { });
    $this->assertEquals($sink->target(), (new Logging($sink))->target());
  }

  #[@test]
  public function no_logging_target() {
    $this->assertEquals('(no logging)', (new Logging(null))->target());
  }

  #[@test, @values([
  #  ['GET /', null],
  #  ['GET / Test', new Error(404, 'Test')],
  #])]
  public function log($expected, $error) {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $logged= [];
    $log= new Logging(new ToFunction(function($kind, $uri, $status, $error= null) use(&$logged) {
      $logged[]= $kind.' '.$uri->path().($error ? ' '.$error->getMessage() : '');
    }));
    $log->log($req->method(), $req->uri(), $res->status(), $error);

    $this->assertEquals([$expected], $logged);
  }

  #[@test]
  public function pipe() {
    $a= new ToFunction(function($kind, $uri, $status, $error= null) { /* a */ });
    $b= new ToFunction(function($kind, $uri, $status, $error= null) { /* b */ });
    $this->assertEquals($b, (new Logging($a))->pipe($b)->sink());
  }

  #[@test]
  public function tee() {
    $a= new ToFunction(function($kind, $uri, $status, $error= null) { /* a */ });
    $b= new ToFunction(function($kind, $uri, $status, $error= null) { /* b */ });
    $this->assertEquals(new ToAllOf($a, $b), (new Logging($a))->tee($b)->sink());
  }

  #[@test]
  public function tee_multiple() {
    $a= new ToFunction(function($kind, $uri, $status, $error= null) { /* a */ });
    $b= new ToFunction(function($kind, $uri, $status, $error= null) { /* b */ });
    $c= new ToFunction(function($kind, $uri, $status, $error= null) { /* c */ });
    $this->assertEquals(new ToAllOf($a, $b, $c), (new Logging($a))->tee($b)->tee($c)->sink());
  }

  #[@test]
  public function pipe_on_no_logging() {
    $sink= new ToFunction(function($kind, $uri, $status, $error= null) { });
    $this->assertEquals($sink, (new Logging(null))->pipe($sink)->sink());
  }

  #[@test]
  public function tee_on_no_logging() {
    $sink= new ToFunction(function($kind, $uri, $status, $error= null) { });
    $this->assertEquals($sink, (new Logging(null))->tee($sink)->sink());
  }
}