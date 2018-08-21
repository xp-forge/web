<?php namespace web\unittest\logging;

use unittest\TestCase;
use web\Error;
use web\Request;
use web\Response;
use web\io\TestInput;
use web\io\TestOutput;
use web\logging\ToAllOf;
use web\logging\ToConsole;
use web\logging\ToFunction;

class ToAllOfTest extends TestCase {

  #[@test]
  public function can_create_without_args() {
    new ToAllOf();
  }

  #[@test]
  public function can_create_with_sink() {
    new ToAllOf(new ToFunction(function($req, $res, $error) { }));
  }

  #[@test]
  public function can_create_with_string() {
    new ToAllOf('-');
  }

  #[@test]
  public function sinks() {
    $a= new ToConsole();
    $b= new ToFunction(function($req, $res, $error) {  });
    $this->assertEquals([$a, $b], (new ToAllOf($a, $b))->sinks());
  }

  #[@test]
  public function sinks_are_merged_when_passed_ToAllOf_instance() {
    $a= new ToConsole();
    $b= new ToFunction(function($req, $res, $error) {  });
    $this->assertEquals([$a, $b], (new ToAllOf(new ToAllOf($a, $b)))->sinks());
  }

  #[@test]
  public function sinks_are_empty_when_created_without_arg() {
    $this->assertEquals([], (new ToAllOf())->sinks());
  }

  #[@test]
  public function targets() {
    $a= new ToConsole();
    $b= new ToFunction(function($req, $res, $error) { });
    $this->assertEquals('(web.logging.ToConsole & web.logging.ToFunction)', (new ToAllOf($a, $b))->target());
  }

  #[@test, @values([
  #  [['a' => ['GET /'], 'b' => ['GET /']], null],
  #  [['a' => ['GET / Test'], 'b' => ['GET / Test']], new Error(404, 'Test')],
  #])]
  public function logs_to_all($expected, $error) {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $logged= ['a' => [], 'b' => []];
    $sink= new ToAllOf(
      new ToFunction(function($req, $res, $error) use(&$logged) {
        $logged['a'][]= $req->method().' '.$req->uri()->path().($error ? ' '.$error->getMessage() : '');
      }),
      new ToFunction(function($req, $res, $error) use(&$logged) {
        $logged['b'][]= $req->method().' '.$req->uri()->path().($error ? ' '.$error->getMessage() : '');
      })
    );
    $sink->log($req, $res, $error);

    $this->assertEquals($expected, $logged);
  }
}