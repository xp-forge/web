<?php namespace web\unittest;

use test\{Assert, Expect, Test, Values};
use web\io\{TestInput, TestOutput};
use web\routing\CannotRoute;
use web\{Application, Environment, Filters, Handler, Request, Response, Routes, NotFound};

class RoutesTest {
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
    new Routes();
  }

  #[Test, Expect(NotFound::class)]
  public function cannot_handle_by_default() {
    (new Routes())->handle(new Request(new TestInput('GET', '/')), new Response());
  }

  #[Test]
  public function routes_initially_empty() {
    Assert::equals([], (new Routes())->routes());
  }

  #[Test]
  public function routes_for_empty_map() {
    Assert::equals([], Routes::cast([])->routes());
  }

  #[Test]
  public function routes_returns_previously_added_map() {
    Assert::equals(
      ['#^[A-Z]+ /#' => $this->handlers['default']],
      Routes::cast(['/' => $this->handlers['default']])->routes()
    );
  }

  #[Test, Values([['/test.txt', '/test\\.txt']])]
  public function routes_escaping($definition, $expected) {
    Assert::equals(
      ['#^[A-Z]+ '.$expected.'/#' => $this->handlers['default']],
      Routes::cast([$definition => $this->handlers['default']])->routes()
    );
  }

  #[Test]
  public function for_self() {
    $routes= new Routes();
    Assert::equals($routes, Routes::cast($routes));
  }

  #[Test, Values([['/api', 'specific'], ['/api/', 'specific'], ['/api/v1', 'specific'], ['/apiv1', 'default'], ['/', 'default']])]
  public function for_map($path, $expected) {
    $fixture= ['/api' => $this->handlers['specific'], '/' => $this->handlers['default']];

    Assert::equals(
      $this->handlers[$expected],
      Routes::cast($fixture)->target(new Request(new TestInput('GET', $path)))
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
    Assert::equals($this->handlers['specific'], Routes::cast($app)
      ->target(new Request(new TestInput('GET', '/api')))
    );
  }

  #[Test]
  public function default() {
    Assert::equals($this->handlers['default'], (new Routes())
      ->default($this->handlers['default'])
      ->target(new Request(new TestInput('GET', '/')))
    );
  }

  #[Test, Values([['/test', 'specific'], ['/test/', 'specific'], ['/test.html', 'default'], ['/', 'default']])]
  public function route_path($url, $expected) {
    Assert::equals($this->handlers[$expected], (new Routes())
      ->route('/test', $this->handlers['specific'])
      ->default($this->handlers['default'])
      ->target(new Request(new TestInput('GET', $url)))
    );
  }

  #[Test, Values([['CONNECT', 'specific'], ['GET', 'default']])]
  public function route_method($verb, $expected) {
    Assert::equals($this->handlers[$expected], (new Routes())
      ->route('CONNECT', $this->handlers['specific'])
      ->default($this->handlers['default'])
      ->target(new Request(new TestInput($verb, '/')))
    );
  }

  #[Test, Values([['GET', 'specific'], ['POST', 'specific'], ['HEAD', 'default']])]
  public function methods($verb, $expected) {
    Assert::equals($this->handlers[$expected], (new Routes())
      ->route('GET|POST', $this->handlers['specific'])
      ->default($this->handlers['default'])
      ->target(new Request(new TestInput($verb, '/')))
    );
  }

  #[Test, Values([['GET', 'specific'], ['POST', 'default'], ['HEAD', 'default']])]
  public function route_target($verb, $expected) {
    Assert::equals($this->handlers[$expected], (new Routes())
      ->route('GET /', $this->handlers['specific'])
      ->default($this->handlers['default'])
      ->target(new Request(new TestInput($verb, '/')))
    );
  }

  #[Test, Values([['/test', 'specific'], ['/test.html', 'specific'], ['/Test.html', 'specific'], ['/test_html', 'default'], ['/', 'default']])]
  public function route_pattern($url, $expected) {
    Assert::equals($this->handlers[$expected], (new Routes())
      ->route('/(?i)test(.html)?', $this->handlers['specific'])
      ->default($this->handlers['default'])
      ->target(new Request(new TestInput('GET', $url)))
    );
  }

  #[Test, Values([['/test/specific', 'specific'], ['/test/default', 'default']])]
  public function route_nested($url, $expected) {
    Assert::equals($this->handlers[$expected], (new Routes())
      ->route('/test', ['/specific' => $this->handlers['specific'], '/default' => $this->handlers['default']])
      ->target(new Request(new TestInput('GET', $url)))
    );
  }

  #[Test, Values([['GET', 'specific'], ['POST', 'default']])]
  public function route_nested_method($verb, $expected) {
    Assert::equals($this->handlers[$expected], (new Routes())
      ->route('/test', ['GET' => $this->handlers['specific'], 'POST' => $this->handlers['default']])
      ->target(new Request(new TestInput($verb, '/test')))
    );
  }

  #[Test, Values(['/api', '//api', '///api', '/test/../api', '/./api', '/../api', '/./../api',])]
  public function request_canonicalized_before_route($requested) {
    Assert::equals($this->handlers['specific'], Routes::cast(['/api' => $this->handlers['specific']])
      ->target(new Request(new TestInput('GET', $requested)))
    );
  }
}