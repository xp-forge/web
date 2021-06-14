<?php namespace web\unittest\filters;

use unittest\TestCase;
use web\filters\BehindProxy;
use web\filters\Invocation;
use web\io\TestInput;
use web\io\TestOutput;
use web\Request;
use web\Response;

class BehindProxyTest extends TestCase {

  #[@test]
  public function can_create() {
    new BehindProxy(['http://remote' => '/']);
  }

  #[@test, @values(['/', '/path', '/path/', '/path/index.html'])]
  public function rewrite_subdomain_to_base($path) {
    $request= new Request(new TestInput('GET', $path));
    $fixture= new BehindProxy(['https://service.example.com/' => '/']);
    $fixture->filter($request, new Response(new TestOutput()), new Invocation(function($req, $res) { }));

    $this->assertEquals('https://service.example.com'.$path, (string)$request->uri());
  }

  #[@test, @values(['/', '/path', '/path/', '/path/index.html'])]
  public function rewrite_path_to_base($path) {
    $request= new Request(new TestInput('GET', $path));
    $fixture= new BehindProxy(['https://example.com/service/' => '/']);
    $fixture->filter($request, new Response(new TestOutput()), new Invocation(function($req, $res) { }));

    $this->assertEquals('https://example.com/service'.$path, (string)$request->uri());
  }

  #[@test, @values(['/', '/path', '/path/', '/path/index.html'])]
  public function rewrite_subdomain_to_path($path) {
    $request= new Request(new TestInput('GET', '/service'.$path));
    $fixture= new BehindProxy(['https://service.example.com/' => '/service']);
    $fixture->filter($request, new Response(new TestOutput()), new Invocation(function($req, $res) { }));

    $this->assertEquals('https://service.example.com'.$path, (string)$request->uri());
  }

  #[@test, @values(['/', '/path', '/path/', '/path/index.html'])]
  public function rewrite_path_to_path($path) {
    $request= new Request(new TestInput('GET', '/service'.$path));
    $fixture= new BehindProxy(['https://example.com/service/' => '/service']);
    $fixture->filter($request, new Response(new TestOutput()), new Invocation(function($req, $res) { }));

    $this->assertEquals('https://example.com/service'.$path, (string)$request->uri());
  }
}