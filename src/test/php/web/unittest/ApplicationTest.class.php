<?php namespace web\unittest;

use lang\IllegalStateException;
use test\{Assert, Expect, Test, Values};
use util\Objects;
use web\io\{TestInput, TestOutput};
use web\{Application, Environment, Error, Filter, Filters, Handler, Request, Response, Routing};

class ApplicationTest {
  private $environment;

  public function __construct() {
    $this->environment= new Environment('dev', '.', 'static', []);
  }

  /** @return iterable */
  private function filters() {
    yield [function($req, $res, $invocation) {
      return $invocation->proceed($req->pass('filtered', 'true'), $res);
    }];
    yield [new class() implements Filter {
      public function filter($req, $res, $invocation) {
        return $invocation->proceed($req->pass('filtered', 'true'), $res);
      }
    }];
  }

  /**
   * Handle given routes
   *
   * @param  function(): var $routes
   * @return var[] A tuple of request and response instances
   */
  private function handle($routes) {
    with ($app= newinstance(Application::class, [$this->environment], ['routes' => $routes])); {
      $request= new Request(new TestInput('GET', '/'));
      $response= new Response(new TestOutput());
      $app->service($request, $response);
      return [$request, $response];
    }
  }

  /**
   * Assertion helper
   *
   * @param  var[] $handled
   * @param  function(): var $routes
   * @throws unittest.AssertionFailedError
   */
  private function assertHandled(&$handled, $routes) {
    $result= $this->handle($routes);
    Assert::equals([$result], $handled);
  }

  #[Test]
  public function can_create() {
    new class($this->environment) extends Application {
      public function routes() { /* Implementation irrelevant for this test */ }
    };
  }

  #[Test]
  public function environment() {
    $app= new class($this->environment) extends Application {
      public function routes() { /* Implementation irrelevant for this test */ }
    };
    Assert::equals($this->environment, $app->environment());
  }

  #[Test]
  public function routing() {
    $routing= new Routing();
    $app= newinstance(Application::class, [$this->environment], [
      'routes' => function() use($routing) { return $routing; }
    ]);
    Assert::equals($routing, $app->routing());
  }

  #[Test]
  public function routes_only_called_once() {
    $routing= new Routing();
    $called= 0;
    $app= newinstance(Application::class, [$this->environment], [
      'routes' => function() use($routing, &$called) {
        if (++$called > 1) {
          throw new IllegalStateException('Should not be reached');
        }
        return $routing;
      }
    ]);
    $app->routing();
    Assert::equals($routing, $app->routing());
  }

  #[Test]
  public function with_routing() {
    $this->assertHandled($handled, function() use(&$handled) {
      return (new Routing())->fallbacks(function($request, $response) use(&$handled) {
        $handled[]= [$request, $response];
      });
    });
  }

  #[Test]
  public function with_route_map() {
    $this->assertHandled($handled, function() use(&$handled) {
      return ['/' => function($request, $response) use(&$handled) {
        $handled[]= [$request, $response];
      }];
    });
  }

  #[Test]
  public function with_handler_function() {
    $this->assertHandled($handled, function() use(&$handled) {
      return function($request, $response) use(&$handled) {
        $handled[]= [$request, $response];
      };
    });
  }

  #[Test]
  public function with_handler_instance() {
    $this->assertHandled($handled, function() use(&$handled) {
      return newinstance(Handler::class, [], [
        'handle' => function($request, $response) use(&$handled) {
          $handled[]= [$request, $response];
        }
      ]);
    });
  }

  #[Test]
  public function dispatch_request() {
    $this->assertHandled($handled, function() use(&$handled) {
      return [
        '/home' => function($request, $response) use(&$handled) {
          $handled[]= [$request, $response];
        },
        '/' => function($request, $response) {
          return $request->dispatch('/home');
        },
      ];
    });
  }

  #[Test]
  public function dispatch_request_without_query() {
    $passed= null;
    $this->handle(function() use(&$passed) {
      return [
        '/login' => function($request, $response) use(&$passed) {
          $passed= $request->params();
        },
        '/' => function($request, $response) {
          return $request->dispatch('/login');
        },
      ];
    });
    Assert::equals([], $passed);
  }

  #[Test]
  public function dispatch_request_with_query() {
    $passed= null;
    $this->handle(function() use(&$passed) {
      return [
        '/deref' => function($request, $response) use(&$passed) {
          $passed= $request->params();
        },
        '/' => function($request, $response) {
          return $request->dispatch('/deref', ['url' => 'http://example.com/']);
        },
      ];
    });
    Assert::equals(['url' => 'http://example.com/'], $passed);
  }

  #[Test, Expect(class: Error::class, message: '/Internal redirect loop/')]
  public function dispatch_request_to_self_causes_error() {
    $this->assertHandled($handled, function() use(&$handled) {
      return [
        '/' => function($request, $response) {
          return $request->dispatch('/');
        },
      ];
    });
  }

  #[Test, Expect(class: Error::class, message: '/Internal redirect loop/')]
  public function dispatch_request_ping_pong_causes_error() {
    $this->assertHandled($handled, function() use(&$handled) {
      return [
        '/home' => function($request, $response) {
          return $request->dispatch('/');
        },
        '/user' => function($request, $response) {
          return $request->dispatch('/home');
        },
        '/' => function($request, $response) {
          return $request->dispatch('/user');
        },
      ];
    });
  }

  #[Test]
  public function dispatch_request_bubbles_up_to_toplevel() {
    $this->assertHandled($handled, function() use(&$handled) {
      return [
        '/home' => function($request, $response) use(&$handled) {
          $handled[]= [$request, $response];
        },
        '/' => new Filters([], function($request, $response) {
          return $request->dispatch('/home');
        }),
      ];
    });
  }

  #[Test]
  public function string_representation() {
    Assert::equals(
      'web.unittest.HelloWorld(static)',
      (new HelloWorld($this->environment))->toString()
    );
  }

  #[Test]
  public function hash_code() {
    Assert::notEquals('', (new HelloWorld($this->environment))->hashCode());
  }

  #[Test]
  public function equals_itself() {
    $app= new HelloWorld($this->environment);
    Assert::equals(0, $app->compareTo($app));
  }

  #[Test]
  public function does_not_equal_clone() {
    $app= new HelloWorld($this->environment);
    Assert::equals(1, $app->compareTo(clone $app));
  }

  #[Test, Values(from: 'filters')]
  public function install_filter($install) {
    list($request, $response)= $this->handle(function() use($install) {
      $this->install($install);

      return function($req, $res) {
        $res->send($req->value('filtered'), 'text/plain');
      };
    });
    Assert::equals('true', $response->output()->body());
  }

  #[Test]
  public function install_multiple_filters() {
    list($request, $response)= $this->handle(function() {
      $this->install([
        function($req, $res, $invocation) {
          return $invocation->proceed($req->pass('filtered', 'true'), $res);
        },
        function($req, $res, $invocation) {
          return $invocation->proceed($req->pass('enhanced', 'twice'), $res);
        }
      ]);

      return function($req, $res) {
        $res->send(Objects::stringOf($req->values()), 'text/plain');
      };
    });
    Assert::equals(
      Objects::stringOf(['filtered' => 'true', 'enhanced' => 'twice']),
      $response->output()->body()
    );
  }
}