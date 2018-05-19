<?php namespace web\unittest\filters;

use unittest\TestCase;
use web\Filter;
use web\filters\BehindProxy;
use web\filters\Invocation;
use web\io\TestInput;
use web\io\TestOutput;
use web\Request;
use web\Response;

class BehindProxyTest extends TestCase {

  #[@test]
  public function can_create() {
    new BehindProxy();
  }

  #[@test]
  public function no_rewriting_performed_by_default() {
    $filter= new BehindProxy();
    $filter->filter(
      new Request(new TestInput('GET', '/')),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('http://localhost/', (string)$request->uri());
  }

  #[@test]
  public function honours_forwarded_host_header_and_defaults_to_https() {
    $filter= new BehindProxy();
    $filter->filter(
      new Request(new TestInput('GET', '/', ['X-Forwarded-Host' => 'example.com'])),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('https://example.com/', (string)$request->uri());
  }

  #[@test]
  public function honours_forwarded_proto_header() {
    $filter= new BehindProxy();
    $filter->filter(
      new Request(new TestInput('GET', '/', ['X-Forwarded-Host' => 'example.com', 'X-Forwarded-Proto' => 'http'])),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('http://example.com/', (string)$request->uri());
  }

  #[@test]
  public function can_force_https_via_using() {
    $filter= (new BehindProxy())->using('https');
    $filter->filter(
      new Request(new TestInput('GET', '/', ['X-Forwarded-Host' => 'example.com', 'X-Forwarded-Proto' => 'http'])),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('https://example.com/', (string)$request->uri());
  }

  #[@test, @values(['/app', '/app/'])]
  public function prefixed_base($base) {
    $filter= (new BehindProxy())->prefixed($base);
    $filter->filter(
      new Request(new TestInput('GET', '/', ['X-Forwarded-Host' => 'example.com'])),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('https://example.com/app/', (string)$request->uri());
  }

  #[@test, @values(['/app', '/app/'])]
  public function stripping_base($base) {
    $filter= (new BehindProxy())->stripping($base);
    $filter->filter(
      new Request(new TestInput('GET', '/app/', ['X-Forwarded-Host' => 'example.com'])),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('https://example.com/', (string)$request->uri());
  }

  #[@test, @values(['/app', '/app/'])]
  public function stripping_base_without_trailing_slash($base) {
    $filter= (new BehindProxy())->stripping($base);
    $filter->filter(
      new Request(new TestInput('GET', '/app', ['X-Forwarded-Host' => 'example.com'])),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('https://example.com/', (string)$request->uri());
  }
}