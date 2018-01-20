<?php namespace web\unittest\handler;

use lang\IllegalArgumentException;
use web\handler\Call;
use web\Request;
use web\Response;
use web\io\TestInput;
use web\io\TestOutput;

class CallTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Call(function($request, $response) { });
  }

  #[@test, @expect(IllegalArgumentException::class), @values([
  #  function() { },
  #  function($one) { },
  #  function($one, $two, $three) { },
  #  '__not_a_function__'
  #])]
  public function invalid($invokeable) {
    new Call($invokeable);
  }

  #[@test]
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