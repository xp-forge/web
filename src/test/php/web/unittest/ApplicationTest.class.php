<?php namespace web\unittest;

use web\Application;
use web\Environment;
use web\Handler;
use web\Request;
use web\Response;
use web\Routing;
use lang\IllegalStateException;

class ApplicationTest extends \unittest\TestCase {
  private $environment;

  /** @return void */
  public function setUp() {
    $this->environment= new Environment('dev', '.', 'static', []);
  }

  /**
   * Assertion helper
   *
   * @param  var[] $handled
   * @param  var $routes
   * @throws unittest.AssertionFailedError
   */
  private function assertHandled(&$handled, $routes) {
    with ($app= newinstance(Application::class, [$this->environment], ['routes' => $routes])); {
      $request= new Request(new TestInput('GET', 'http://test.example.com/'));
      $response= new Response();
      $app->service($request, $response);
    }
    $this->assertEquals([[$request, $response]], $handled);
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
}