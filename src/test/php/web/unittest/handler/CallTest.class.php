<?php namespace web\unittest\handler;

use lang\IllegalArgumentException;
use unittest\{Expect, Test, TestCase, Values};
use web\handler\Call;
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class CallTest extends TestCase {

  /** @return iterable */
  private function invalid() {
    yield [function() { }];
    yield [function($one) { }];
    yield [function($one, $two, $three) { }];
    yield ['__not_a_function__'];
  }

  #[Test]
  public function can_create() {
    new Call(function($request, $response) { });
  }

  #[Test, Expect(IllegalArgumentException::class), Values('invalid')]
  public function create_with_invalid($invokeable) {
    new Call($invokeable);
  }

  #[Test]
  public function handle() {
    $handled= [];
    $invokeable= function($request, $response) use(&$handled) {
      $handled[]= [$request, $response];
    };

    $request= new Request(new TestInput('GET', '/'));
    $response= new Response(new TestOutput());
    (new Call($invokeable))->handle($request, $response);

    $this->assertEquals([[$request, $response]], $handled);
  }
}