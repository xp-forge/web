<?php namespace web\unittest\logging;

use test\{Assert, Test, Values};
use web\Error;
use web\logging\{ToAllOf, ToConsole, ToFunction};

class ToAllOfTest {

  /** @return iterable */
  private function arguments() {
    yield [['a' => ['GET /'], 'b' => ['GET /']], []];
    yield [['a' => ['GET / Test'], 'b' => ['GET / Test']], ['error' => new Error(404, 'Test')]];
  }

  #[Test]
  public function can_create_without_args() {
    new ToAllOf();
  }

  #[Test]
  public function can_create_with_sink() {
    new ToAllOf(new ToFunction(function($status, $method, $uri, $hints) { }));
  }

  #[Test]
  public function can_create_with_string() {
    new ToAllOf('-');
  }

  #[Test]
  public function sinks() {
    $a= new ToConsole();
    $b= new ToFunction(function($status, $method, $uri, $hints) {  });
    Assert::equals([$a, $b], (new ToAllOf($a, $b))->sinks());
  }

  #[Test]
  public function sinks_are_merged_when_passed_ToAllOf_instance() {
    $a= new ToConsole();
    $b= new ToFunction(function($status, $method, $uri, $hints) {  });
    Assert::equals([$a, $b], (new ToAllOf(new ToAllOf($a, $b)))->sinks());
  }

  #[Test]
  public function sinks_are_empty_when_created_without_arg() {
    Assert::equals([], (new ToAllOf())->sinks());
  }

  #[Test]
  public function targets() {
    $a= new ToConsole();
    $b= new ToFunction(function($status, $method, $uri, $hints) { });
    Assert::equals('(web.logging.ToConsole & web.logging.ToFunction)', (new ToAllOf($a, $b))->target());
  }

  #[Test, Values(from: 'arguments')]
  public function logs_to_all($expected, $hints) {
    $logged= ['a' => [], 'b' => []];
    $sink= new ToAllOf(
      new ToFunction(function($status, $method, $uri, $hints) use(&$logged) {
        $logged['a'][]= $method.' '.$uri.($hints ? ' '.$hints['error']->getMessage() : '');
      }),
      new ToFunction(function($status, $method, $uri, $hints) use(&$logged) {
        $logged['b'][]= $method.' '.$uri.($hints ? ' '.$hints['error']->getMessage() : '');
      })
    );
    $sink->log(200, 'GET', '/', $hints);

    Assert::equals($expected, $logged);
  }
}