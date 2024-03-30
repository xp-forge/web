<?php namespace web\unittest;

use test\{Assert, Expect, Test, Values};
use web\io\{TestInput, TestOutput};
use web\{Application, Environment, Filters, Handler, Request, Response, CannotRoute, Routing};

class RoutingTest {
  private $handlers;

  public function __construct() {
    $this->handlers= [
      'specific' => new class() implements Handler { public $name= 'specific'; public function handle($req, $res) { }},
      'default'  => new class() implements Handler { public $name= 'default'; public function handle($req, $res) { }},
      'error'    => new class() implements Handler { public $name= 'error'; public function handle($req, $res) { }},
    ];
  }

  #[Test]
  public function can_create() {
    new Routing();
  }

  #[Test, Expect(CannotRoute::class)]
  public function cannot_handle_by_default() {
    (new Routing())->handle(new Request(new TestInput('GET', '/')), new Response());
  }

  #[Test]
  public function routes_initially_empty() {
    Assert::equals([], (new Routing())->routes());
  }

  #[Test]
  public function routes_for_empty_map() {
    Assert::equals([], Routing::cast([])->routes());
  }

  #[Test]
  public function routes_returns_previously_added_map() {
    Assert::equals(
      ['#^[A-Z]+ /#' => $this->handlers['default']],
      Routing::cast(['/' => $this->handlers['default']])->routes()
    );
  }

  #[Test]
  public function for_self() {
    $routes= new Routing();
    Assert::equals($routes, Routing::cast($routes));
  }

  #[Test, Values([['/api', 'specific'], ['/api/', 'specific'], ['/api/v1', 'specific'], ['/apiv1', 'default'], ['/', 'default']])]
  public function for_map($path, $expected) {
    $fixture= ['/api' => $this->handlers['specific'], '/' => $this->handlers['default']];

    Assert::equals(
      $this->handlers[$expected],
      Routing::cast($fixture)->route(new Request(new TestInput('GET', $path)))
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
    Assert::equals($this->handlers['specific'], Routing::cast($app)
      ->route(new Request(new TestInput('GET', '/api')))
    );
  }

  #[Test]
  public function fallbacks() {
    Assert::equals($this->handlers['default'], (new Routing())
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput('GET', '/')))
    );
  }

  #[Test, Values([['/test', 'specific'], ['/test/', 'specific'], ['/test.html', 'default'], ['/', 'default']])]
  public function matching_path($url, $expected) {
    Assert::equals($this->handlers[$expected], (new Routing())
      ->matching('/test', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput('GET', $url)))
    );
  }

  #[Test, Values([['CONNECT', 'specific'], ['GET', 'default']])]
  public function matching_method($verb, $expected) {
    Assert::equals($this->handlers[$expected], (new Routing())
      ->matching('CONNECT', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, '/')))
    );
  }

  #[Test, Values([['GET', 'specific'], ['POST', 'specific'], ['HEAD', 'default']])]
  public function methods($verb, $expected) {
    Assert::equals($this->handlers[$expected], (new Routing())
      ->matching('GET|POST', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, '/')))
    );
  }

  #[Test, Values([['GET', 'specific'], ['POST', 'default'], ['HEAD', 'default']])]
  public function matching_target($verb, $expected) {
    Assert::equals($this->handlers[$expected], (new Routing())
      ->matching('GET /', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput($verb, '/')))
    );
  }

  #[Test, Values([['/test', 'specific'], ['/test.html', 'specific'], ['/', 'default']])]
  public function matching_paths($url, $expected) {
    Assert::equals($this->handlers[$expected], (new Routing())
      ->matching(['/test', '/test.html'], $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput('GET', $url)))
    );
  }

  #[Test, Values([['/test', 'specific'], ['/test.html', 'specific'], ['/', 'default']])]
  public function matching_pattern($url, $expected) {
    Assert::equals($this->handlers[$expected], (new Routing())
      ->matching('/test(.html)?', $this->handlers['specific'])
      ->fallbacks($this->handlers['default'])
      ->route(new Request(new TestInput('GET', $url)))
    );
  }

  #[Test, Values(['/api', '//api', '///api', '/test/../api', '/./api', '/../api', '/./../api',])]
  public function request_canonicalized_before_matching($requested) {
    Assert::equals($this->handlers['specific'], Routing::cast(['/api' => $this->handlers['specific']])
      ->route(new Request(new TestInput('GET', $requested)))
    );
  }
}