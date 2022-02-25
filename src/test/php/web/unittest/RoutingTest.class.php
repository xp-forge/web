<?php namespace web\unittest;

use unittest\{Expect, Test, TestCase, Values};
use web\io\{TestInput, TestOutput};
use web\routing\{CannotRoute, Target};
use web\{Application, Environment, Handler, Request, Response, Route, Routing};

class RoutingTest extends TestCase {
  private $handlers;

  /** @return void */
  public function setUp() {
    $this->handlers= [
      'specific' => new class() implements Handler { public $name= 'specific'; public function handle($req, $res) { }},
      'default'  => new class() implements Handler { public $name= 'default'; public function handle($req, $res) { }}
    ];
  }

  #[Test]
  public function can_create() {
    new Routing();
  }

  #[Test, Expect(CannotRoute::class)]
  public function cannot_service_by_default() {
    (new Routing())->service(new Request(new TestInput('GET', '/')), new Response());
  }

  #[Test]
  public function routes_initially_empty() {
    $this->assertEquals([], (new Routing())->routes());
  }

  #[Test]
  public function routes_for_empty_map() {
    $this->assertEquals([], Routing::cast([])->routes());
  }

  #[Test]
  public function routes_returns_previously_added_map() {
    $route= new Route(new Target('GET', '/'), $this->handlers['default']);
    $this->assertEquals([$route], (new Routing())->with($route)->routes());
  }

  #[Test]
  public function for_self() {
    $routes= new Routing();
    $this->assertEquals($routes, Routing::cast($routes));
  }

  #[Test]
  public function for_map() {
    $this->assertEquals($this->handlers['specific'], Routing::cast(['/api' => $this->handlers['specific']])
      ->route(new Request(new TestInput('GET', '/api')))
    );
  }

  #[Test]
  public function for_application() {
    $app= new class($this->handlers) extends Application {
      private $handlers;

      public function __construct($handlers) {
        parent::__construct(new Environment('test'));
        $this->handlers= $handlers;
      }

      public function routes() {
        return ['/api' => $this->handlers['specific']];
      }
    };
    $this->assertEquals($this->handlers['specific'], Routing::cast($app)
      ->route(new Request(new TestInput('GET', '/api')))
    );
  }

  #[Test]
  public function fallbacks() {
    $this->assertEquals($this->handlers['default'], (new Routing())
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput('GET', '/')))
    );
  }

  #[Test, Values([['/test', 'specific'], ['/test/', 'specific'], ['/test.html', 'default'], ['/', 'default']])]
  public function matching_path($url, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->matching('/test', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput('GET', $url)))
    );
  }

  #[Test, Values([['CONNECT', 'specific'], ['GET', 'default']])]
  public function matching_method($verb, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->matching('CONNECT', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, '/')))
    );
  }

  #[Test, Values([['GET', 'specific'], ['POST', 'specific'], ['HEAD', 'default']])]
  public function methods($verb, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->matching('GET|POST', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, '/')))
    );
  }

  #[Test, Values([['GET', 'specific'], ['POST', 'default'], ['HEAD', 'default']])]
  public function matching_target($verb, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->matching('GET /', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, '/')))
    );
  }

  #[Test, Values([['/test', 'specific'], ['/test.html', 'specific'], ['/', 'default']])]
  public function matching_paths($url, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->matching(['/test', '/test.html'], $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput('GET', $url)))
    );
  }

  #[Test, Values([['GET', 'specific'], ['POST', 'default'], ['HEAD', 'specific']])]
  public function mapping($verb, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->mapping(
        function($request) { return in_array($request->method(), ['GET', 'HEAD']); },
        $this->handlers['specific']
      )
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, '/')))
    );
  }

  #[Test, Values([['GET', 'specific'], ['POST', 'default'], ['HEAD', 'specific']])]
  public function with($verb, $expected) {
    $this->assertEquals($this->handlers[$expected], (new Routing())
      ->with(new Route(new Target(['GET', 'HEAD'], '*'), $this->handlers['specific']))
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, '/')))
    );
  }

  #[Test, Values(['/api', '//api', '///api', '/test/../api', '/./api', '/../api', '/./../api',])]
  public function request_canonicalized_before_matching($requested) {
    $this->assertEquals($this->handlers['specific'], Routing::cast(['/api' => $this->handlers['specific']])
      ->route(new Request(new TestInput('GET', $requested)))
    );
  }
}