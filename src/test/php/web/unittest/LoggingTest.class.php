<?php namespace web\unittest;

use web\Logging;
use web\Request;
use web\Response;
use web\io\TestInput;
use web\io\TestOutput;
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
}