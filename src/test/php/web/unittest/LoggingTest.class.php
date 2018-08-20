<?php namespace web\unittest;

use web\Logging;
use web\Request;
use web\Response;
use web\io\TestInput;
use web\io\TestOutput;
use web\log\ToAllOf;
use web\log\ToFunction;

class LoggingTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Logging(null);
  }

  #[@test]
  public function can_create_with_sink() {
    new Logging(new ToFunction(function($req, $res, $message) { }));
  }

  #[@test]
  public function target() {
    $sink= new ToFunction(function($req, $res, $message) { });
    $this->assertEquals($sink->target(), (new Logging($sink))->target());
  }

  #[@test]
  public function no_logging_target() {
    $this->assertEquals('(no logging)', (new Logging(null))->target());
  }

  #[@test, @values([
  #  ['GET /', null],
  #  ['GET / Test', 'Test'],
  #])]
  public function log($expected, $message) {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $logged= [];
    $log= new Logging(new ToFunction(function($req, $res, $message) use(&$logged) {
      $logged[]= $req->method().' '.$req->uri()->path().($message ? ' '.$message : '');
    }));
    $log->log($req, $res, $message);

    $this->assertEquals([$expected], $logged);
  }

  #[@test]
  public function pipe() {
    $a= new ToFunction(function($req, $res, $message) { /* a */ });
    $b= new ToFunction(function($req, $res, $message) { /* b */ });
    $this->assertEquals($b, (new Logging($a))->pipe($b)->sink());
  }

  #[@test]
  public function tee() {
    $a= new ToFunction(function($req, $res, $message) { /* a */ });
    $b= new ToFunction(function($req, $res, $message) { /* b */ });
    $this->assertEquals(new ToAllOf($a, $b), (new Logging($a))->tee($b)->sink());
  }

  #[@test]
  public function pipe_on_no_logging() {
    $sink= new ToFunction(function($req, $res, $message) { });
    $this->assertEquals($sink, (new Logging(null))->pipe($sink)->sink());
  }

  #[@test]
  public function tee_on_no_logging() {
    $sink= new ToFunction(function($req, $res, $message) { });
    $this->assertEquals($sink, (new Logging(null))->tee($sink)->sink());
  }
}