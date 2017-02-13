<?php namespace web\unittest\handler;

use web\handler\Call;
use web\Request;
use web\Response;
use lang\IllegalArgumentException;

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

    $request= new Request('GET', 'http://localhost');
    $response= new Response();
    (new Call($invokeable))->handle($request, $response);

    $this->assertEquals([[$request, $response]], $handled);
  }
}