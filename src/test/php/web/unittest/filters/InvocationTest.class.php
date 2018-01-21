<?php namespace web\unittest\filters;

use web\filters\Invocation;
use web\Request;
use web\Response;
use web\io\TestInput;
use web\io\TestOutput;
use web\Filter;
use lang\IllegalStateException;

class InvocationTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Invocation(function($req, $res) { }, []);
  }

  #[@test]
  public function invokes_handler() {
    $invoked= false;
    $fixture= new Invocation(function($req, $res) use(&$invoked) { $invoked= true; }, []);
    $fixture->proceed(new Request(new TestInput('GET', '/')), new Response(new TestOutput()));
    $this->assertTrue($invoked);
  }

  #[@test]
  public function filters_can_pass_values_into_request() {
    $invoked= false;
    $fixture= new Invocation(function($req, $res) use(&$invoked) { $invoked= $req->value('invoked'); }, [newinstance(Filter::class, [], [
      'filter' => function($req, $res, $invocation) {
        $req->pass('invoked', true);
        return $invocation->proceed($req, $res);
      }
    ])]);
    $fixture->proceed(new Request(new TestInput('GET', '/')), new Response(new TestOutput()));
    $this->assertTrue($invoked);
  }

  #[@test]
  public function filters_can_prevent_handler_invocation() {
    $fixture= new Invocation(function($req, $res) { throw new IllegalStateException('Should not be called'); }, [newinstance(Filter::class, [], [
      'filter' => function($req, $res, $invocation) {
        // Not calling proceed() here
      }
    ])]);
    $fixture->proceed(new Request(new TestInput('GET', '/')), new Response(new TestOutput()));
  }
}