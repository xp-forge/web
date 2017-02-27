<?php namespace web\unittest;

use web\Request;
use web\Response;
use web\Routing;
use web\Route;
use web\Handler;
use web\routing\CannotRoute;
use web\routing\Target;

class RoutingTest extends \unittest\TestCase {
  private $handlers;

  /** @return void */
  public function setUp() {
    $noop= function($request, $response) { };
    $this->handlers= [
      'specific' => newinstance(Handler::class, [], ['name' => 'specific', 'handle' => $noop]),
      'default'  => newinstance(Handler::class, [], ['name' => 'default', 'handle' => $noop])
    ];
  }

  #[@test]
  public function can_create() {
    new Routing();
  }

  #[@test, @expect(CannotRoute::class)]
  public function cannot_service_by_default() {
    (new Routing())->service(new Request(new TestInput('GET', 'http://localhost/')), new Response());
  }

  #[@test]
  public function routes_initially_empty() {
    $this->assertEquals([], (new Routing())->routes());
  }

  #[@test]
  public function routes_for_empty_map() {
    $this->assertEquals([], Routing::cast([])->routes());
  }

  #[@test]
  public function routes_returns_previously_added_map() {
    $route= new Route(new Target('GET', '/'), $this->handlers['default']);
    $this->assertEquals([$route], (new Routing())->with($route)->routes());
  }

  #[@test]
  public function for_self() {
    $routes= new Routing();
    $this->assertEquals($routes, Routing::cast($routes));
  }

  #[@test]
  public function for_map() {
    $this->assertEquals($this->handlers['specific'], Routing::cast(['/api' => $this->handlers['specific']])
      ->route(new Request(new TestInput('GET', 'http://localhost/api')))
    );
  }

  #[@test]
  public function fallbacks() {
    $this->assertEquals($this->handlers['default'], (new Routing())
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput('GET', 'http://localhost/')))
    );
  }

  #[@test, @values([
  #  ['http://localhost/test', 'specific'],
  #  ['http://localhost/test.html', 'default'],
  #  ['http://localhost/', 'default']
  #])]
  public function matching_path($url, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->matching('/test', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput('GET', $url)))
    );
  }

  #[@test, @values([
  #  ['CONNECT', 'specific'],
  #  ['GET', 'default']
  #])]
  public function matching_method($verb, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->matching('CONNECT', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, 'http://localhost/')))
    );
  }

  #[@test, @values([
  #  ['GET', 'specific'],
  #  ['POST', 'specific'],
  #  ['HEAD', 'default']
  #])]
  public function methods($verb, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->matching('GET|POST', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, 'http://localhost/')))
    );
  }

  #[@test, @values([
  #  ['GET', 'specific'],
  #  ['POST', 'default'],
  #  ['HEAD', 'default']
  #])]
  public function matching_target($verb, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->matching('GET /', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, 'http://localhost/')))
    );
  }

  #[@test, @values([
  #  ['GET', 'specific'],
  #  ['POST', 'default'],
  #  ['HEAD', 'specific']
  #])]
  public function mapping($verb, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->mapping(
        function($request) { return in_array($request->method(), ['GET', 'HEAD']); },
        $this->handlers['specific']
      )
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, 'http://localhost/')))
    );
  }

  #[@test, @values([
  #  ['GET', 'specific'],
  #  ['POST', 'default'],
  #  ['HEAD', 'specific']
  #])]
  public function with($verb, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->with(new Route(new Target(['GET', 'HEAD'], '*'), $this->handlers['specific']))
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, 'http://localhost/')))
    );
  }
}