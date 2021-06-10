<?php namespace web\unittest;

use unittest\{Test, TestCase};
use web\io\{TestInput, TestOutput};
use web\{Filter, Filters, Request, Response};

class FiltersTest extends TestCase {

  /**
   * Handle filters
   *
   * @param  web.Filter|function(web.Request, web.Response, web.filters.Invocation): var $filter
   * @param  web.Handler|function(web.Request, web.Response): var $handler
   * @return web.Response
   */
  private function filter($filter, $handler) {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $fixture= new Filters($filter ? [$filter] : [], $handler);
    foreach ($fixture->handle($req, $res) ?? [] as $_) { }

    return $res;
  }

  /** @return iterable */
  private function handlers() {
    yield [function($req, $res) { }];
    yield [function($req, $res) { return null; }];
    yield [function($req, $res) { return $req->dispatch('/'); }];
    yield [function($req, $res) { yield; }];
  }

  #[Test]
  public function can_create() {
    new Filters([], function($req, $res) { });
  }

  #[Test]
  public function runs_handler() {
    $invoked= [];
    $this->filter(null, function($req, $res) use(&$invoked) {
      $invoked[]= $req->method().' '.$req->uri()->path();
    });
    $this->assertEquals(['GET /'], $invoked);
  }

  #[Test]
  public function filter_instance() {
    $filter= new class() implements Filter {
      public function filter($req, $res, $invocation) {
        $req->rewrite($req->uri()->using()->path('/rewritten')->create());
        return $invocation->proceed($req, $res);
      }
    };

    $invoked= [];
    $this->filter($filter, function($req, $res) use(&$invoked) {
      $invoked[]= $req->method().' '.$req->uri()->path();
    });
    $this->assertEquals(['GET /rewritten'], $invoked);
  }

  #[Test]
  public function filter_function() {
    $filter= function($req, $res, $invocation) {
      $req->rewrite($req->uri()->using()->path('/rewritten')->create());
      return $invocation->proceed($req, $res);
    };

    $invoked= [];
    $this->filter($filter, function($req, $res) use(&$invoked) {
      $invoked[]= $req->method().' '.$req->uri()->path();
    });
    $this->assertEquals(['GET /rewritten'], $invoked);
  }

  #[Test, Values('handlers')]
  public function filter_yielding_handler_using_yield_from($handler) {
    $filter= function($req, $res, $invocation) {
      $res->header('X-Filtered', true);
      try {
        yield from $invocation->proceed($req, $res);
      } finally {
        $res->header('X-Completed', true);
      }
    };

    $res= $this->filter($filter, $handler);
    $this->assertEquals(['X-Filtered' => '1', 'X-Completed' => '1'], $res->headers());
  }

  #[Test, Values('handlers')]
  public function filter_yielding_handler_using_return($handler) {
    $filter= function($req, $res, $invocation) {
      $res->header('X-Filtered', true);
      try {
        return $invocation->proceed($req, $res);
      } finally {
        $res->header('X-Completed', true);
      }
    };

    $res= $this->filter($filter, $handler);
    $this->assertEquals(['X-Filtered' => '1', 'X-Completed' => '1'], $res->headers());
  }

  /** @deprecated Filters should always use `yield from` or `return`! */
  #[Test, Values('handlers')]
  public function filter_without_return($handler) {
    $filter= function($req, $res, $invocation) {
      $res->header('X-Filtered', true);
      try {
        $invocation->proceed($req, $res);
      } finally {
        $res->header('X-Completed', true);
      }
    };

    $res= $this->filter($filter, $handler);
    $this->assertEquals(['X-Filtered' => '1', 'X-Completed' => '1'], $res->headers());
  }
}