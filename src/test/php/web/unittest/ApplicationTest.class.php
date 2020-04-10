<?php namespace web\unittest;

use lang\IllegalStateException;
use web\Application;
use web\Environment;
use web\Error;
use web\Filters;
use web\Handler;
use web\Request;
use web\Response;
use web\Routing;
use web\io\TestInput;
use web\io\TestOutput;

class ApplicationTest extends \unittest\TestCase {
  private $environment;

  /** @return void */
  public function setUp() {
    $this->environment= new Environment('dev', '.', 'static', []);
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
      $response= new Response();
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
    $this->assertEquals([$result], $handled);
  }

  #[@test]
  public function can_create() {
    newinstance(Application::class, [$this->environment], [
      'routes' => function() { /* Implementation irrelevant for this test */ }
    ]);
  }

  #[@test]
  public function routing() {
    $routing= new Routing();
    $app= newinstance(Application::class, [$this->environment], [
      'routes' => function() use($routing) { return $routing; }
    ]);
    $this->assertEquals($routing, $app->routing());
  }

  #[@test]
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
    $this->assertEquals($routing, $app->routing());
  }

  #[@test]
  public function with_routing() {
    $this->assertHandled($handled, function() use(&$handled) {
      return (new Routing())->fallbacks(function($request, $response) use(&$handled) {
        $handled[]= [$request, $response];
      });
    });
  }

  #[@test]
  public function with_route_map() {
    $this->assertHandled($handled, function() use(&$handled) {
      return ['/' => function($request, $response) use(&$handled) {
        $handled[]= [$request, $response];
      }];
    });
  }

  #[@test]
  public function with_handler_function() {
    $this->assertHandled($handled, function() use(&$handled) {
      return function($request, $response) use(&$handled) {
        $handled[]= [$request, $response];
      };
    });
  }

  #[@test]
  public function with_handler_instance() {
    $this->assertHandled($handled, function() use(&$handled) {
      return newinstance(Handler::class, [], [
        'handle' => function($request, $response) use(&$handled) {
          $handled[]= [$request, $response];
        }
      ]);
    });
  }

  #[@test]
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

  #[@test]
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
    $this->assertEquals([], $passed);
  }

  #[@test]
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
    $this->assertEquals(['url' => 'http://example.com/'], $passed);
  }

  #[@test, @expect(['class' => Error::class, 'withMessage' => '/Internal redirect loop/'])]
  public function dispatch_request_to_self_causes_error() {
    $this->assertHandled($handled, function() use(&$handled) {
      return [
        '/' => function($request, $response) {
          return $request->dispatch('/');
        },
      ];
    });
  }

  #[@test, @expect(['class' => Error::class, 'withMessage' => '/Internal redirect loop/'])]
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

  #[@test]
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
}