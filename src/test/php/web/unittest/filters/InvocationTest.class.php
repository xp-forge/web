<?php namespace web\unittest\filters;

use lang\IllegalStateException;
use unittest\Test;
use web\filters\Invocation;
use web\io\{TestInput, TestOutput};
use web\{Filter, Request, Response};

class InvocationTest extends \unittest\TestCase {

  #[Test]
  public function can_create_with_routing_function() {
    new Invocation(function($req, $res) { }, []);
  }

  #[Test]
  public function can_create_with_routing_map() {
    new Invocation(['/' => function($req, $res) { }], []);
  }

  #[Test]
  public function invokes_handler() {
    $invoked= false;
    $fixture= new Invocation(function($req, $res) use(&$invoked) { $invoked= true; }, []);
    $fixture->proceed(new Request(new TestInput('GET', '/')), new Response(new TestOutput()));
    $this->assertTrue($invoked);
  }

  #[Test]
  public function filters_can_pass_values_into_request() {
    $invoked= false;
    $handler= function($req, $res) use(&$invoked) { $invoked= $req->value('invoked'); };
    $fixture= new Invocation($handler, [new class() implements Filter {
      public function filter($req, $res, $invocation) {
        $req->pass('invoked', true);
        return $invocation->proceed($req, $res);
      }
    }]);
    $fixture->proceed(new Request(new TestInput('GET', '/')), new Response(new TestOutput()));
    $this->assertTrue($invoked);
  }

  #[Test]
  public function filters_are_called_in_the_order_they_are_passed() {
    $invoked= [];
    $fixture= new Invocation(function($req, $res) { }, [
      newinstance(Filter::class, [], [
        'filter' => function($req, $res, $invocation) use(&$invoked) {
          $invoked[]= 'First';
          return $invocation->proceed($req, $res);
        }
      ]),
      newinstance(Filter::class, [], [
        'filter' => function($req, $res, $invocation) use(&$invoked) {
          $invoked[]= 'Second';
          return $invocation->proceed($req, $res);
        }
      ])
    ]);

    $fixture->proceed(new Request(new TestInput('GET', '/')), new Response(new TestOutput()));
    $this->assertEquals(['First', 'Second'], $invoked);
  }

  #[Test]
  public function filters_can_prevent_handler_invocation() {
    $handler= function($req, $res) { throw new IllegalStateException('Should not be called'); };
    $fixture= new Invocation($handler, [new class() implements Filter {
      public function filter($req, $res, $invocation) {
        // Not calling proceed() here
      }
    }]);
    $fixture->proceed(new Request(new TestInput('GET', '/')), new Response(new TestOutput()));
  }

  #[Test]
  public function returns_iterable() {
    $fixture= new Invocation(function($req, $res) { }, []);
    $this->assertEquals([], $fixture->proceed(new Request(new TestInput('GET', '/')), new Response(new TestOutput())));
  }

  #[Test]
  public function returns_iterable_if_filtered() {
    $fixture= new Invocation(function($req, $res) { }, [new class() implements Filter {
      public function filter($req, $res, $invocation) {
        return $invocation->proceed($req, $res);
      }
    }]);
    $this->assertEquals([], $fixture->proceed(new Request(new TestInput('GET', '/')), new Response(new TestOutput())));
  }
}