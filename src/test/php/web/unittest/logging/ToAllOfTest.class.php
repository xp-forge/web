<?php namespace web\unittest\logging;

use test\{Assert, Test, Values};
use web\io\{TestInput, TestOutput};
use web\logging\{ToAllOf, ToConsole, ToFunction};
use web\{Error, Request, Response};

class ToAllOfTest {

  /** @return iterable */
  private function arguments() {
    yield [['a' => ['GET /'], 'b' => ['GET /']], null];
    yield [['a' => ['GET / Test'], 'b' => ['GET / Test']], new Error(404, 'Test')];
  }

  #[Test]
  public function can_create_without_args() {
    new ToAllOf();
  }

  #[Test]
  public function can_create_with_sink() {
    new ToAllOf(new ToFunction(function($req, $res, $error) { }));
  }

  #[Test]
  public function can_create_with_string() {
    new ToAllOf('-');
  }

  #[Test]
  public function sinks() {
    $a= new ToConsole();
    $b= new ToFunction(function($req, $res, $error) {  });
    Assert::equals([$a, $b], (new ToAllOf($a, $b))->sinks());
  }

  #[Test]
  public function sinks_are_merged_when_passed_ToAllOf_instance() {
    $a= new ToConsole();
    $b= new ToFunction(function($req, $res, $error) {  });
    Assert::equals([$a, $b], (new ToAllOf(new ToAllOf($a, $b)))->sinks());
  }

  #[Test]
  public function sinks_are_empty_when_created_without_arg() {
    Assert::equals([], (new ToAllOf())->sinks());
  }

  #[Test]
  public function targets() {
    $a= new ToConsole();
    $b= new ToFunction(function($req, $res, $error) { });
    Assert::equals('(web.logging.ToConsole & web.logging.ToFunction)', (new ToAllOf($a, $b))->target());
  }

  #[Test, Values(from: 'arguments')]
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

    Assert::equals($expected, $logged);
  }
}