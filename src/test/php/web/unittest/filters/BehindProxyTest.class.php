<?php namespace web\unittest\filters;

use lang\IllegalArgumentException;
use unittest\{Assert, Expect, Test, Values};
use web\filters\{BehindProxy, Invocation};
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class BehindProxyTest {

  #[Test]
  public function can_create() {
    new BehindProxy(['http://remote' => '/']);
  }

  #[Test, Expect(IllegalArgumentException::class), Values([[[]], [[1]], [[1, 2, 3]]])]
  public function raises_exception_for_invalid($arg) {
    new BehindProxy($arg);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function raises_exception_for_relative() {
    new BehindProxy(['/relative' => '/']);
  }

  #[Test, Values(['/', '/path', '/path/', '/path/index.html'])]
  public function rewrite_subdomain_to_base($path) {
    $request= new Request(new TestInput('GET', $path));
    $fixture= new BehindProxy(['https://service.example.com/' => '/']);
    $fixture->filter($request, new Response(new TestOutput()), new Invocation(function($req, $res) { }));

    Assert::equals('https://service.example.com'.$path, (string)$request->uri());
  }

  #[Test, Values(['/', '/path', '/path/', '/path/index.html'])]
  public function rewrite_path_to_base($path) {
    $request= new Request(new TestInput('GET', $path));
    $fixture= new BehindProxy(['https://example.com/service/' => '/']);
    $fixture->filter($request, new Response(new TestOutput()), new Invocation(function($req, $res) { }));

    Assert::equals('https://example.com/service'.$path, (string)$request->uri());
  }

  #[Test, Values(['/', '/path', '/path/', '/path/index.html'])]
  public function rewrite_subdomain_to_path($path) {
    $request= new Request(new TestInput('GET', '/service'.$path));
    $fixture= new BehindProxy(['https://service.example.com/' => '/service']);
    $fixture->filter($request, new Response(new TestOutput()), new Invocation(function($req, $res) { }));

    Assert::equals('https://service.example.com'.$path, (string)$request->uri());
  }

  #[Test, Values(['/', '/path', '/path/', '/path/index.html'])]
  public function rewrite_path_to_path($path) {
    $request= new Request(new TestInput('GET', '/service'.$path));
    $fixture= new BehindProxy(['https://example.com/service/' => '/service']);
    $fixture->filter($request, new Response(new TestOutput()), new Invocation(function($req, $res) { }));

    Assert::equals('https://example.com/service'.$path, (string)$request->uri());
  }
}