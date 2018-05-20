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
  const PROXY_ADDRESS = '127.0.0.2';
  const PROXY_NETWORK = '127.0.0.1/8';

  /**
   * Creates a request from the proxy
   *
   * @param  string $path
   * @param  [:string] $headers
   * @return web.Request
   */
  private function proxyRequest($path, $headers) {
    return new Request(new TestInput('GET', $path, array_merge($headers, [
      'Remote-Addr'     => self::PROXY_ADDRESS,
      'X-Forwarded-For' => '127.0.0.1'
    ])));
  }

  #[@test]
  public function can_create() {
    new BehindProxy(self::PROXY_ADDRESS);
  }

  #[@test, @values([
  #  ['172.17.31.213', false],
  #  ['127.0.0.1', false],
  #  [self::PROXY_ADDRESS, true],
  #])]
  public function trusts_addr($addr, $trusted) {
    $this->assertEquals($trusted, (new BehindProxy(self::PROXY_ADDRESS))->trusts($addr));
  }

  #[@test, @values([
  #  ['172.17.31.213', false],
  #  ['127.0.0.1', true],
  #  [self::PROXY_ADDRESS, true],
  #])]
  public function trusts_network($addr, $trusted) {
    $this->assertEquals($trusted, (new BehindProxy(self::PROXY_NETWORK))->trusts($addr));
  }

  #[@test]
  public function no_rewriting_performed_by_default() {
    (new BehindProxy(self::PROXY_ADDRESS))->filter(
      new Request(new TestInput('GET', '/')),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('http://localhost/', (string)$request->uri());
  }

  #[@test]
  public function honours_forwarded_host_header_and_defaults_to_https() {
    (new BehindProxy(self::PROXY_ADDRESS))->filter(
      $this->proxyRequest('/', ['X-Forwarded-Host' => 'example.com']),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('https://example.com/', (string)$request->uri());
  }

  #[@test]
  public function honours_forwarded_proto_header() {
    (new BehindProxy(self::PROXY_ADDRESS))->filter(
      $this->proxyRequest('/', ['X-Forwarded-Host' => 'example.com', 'X-Forwarded-Proto' => 'http']),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('http://example.com/', (string)$request->uri());
  }

  #[@test]
  public function can_force_https_via_using() {
    (new BehindProxy(self::PROXY_ADDRESS))->using('https')->filter(
      $this->proxyRequest('/', ['X-Forwarded-Host' => 'example.com', 'X-Forwarded-Proto' => 'http']),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('https://example.com/', (string)$request->uri());
  }

  #[@test, @values(['/app', '/app/'])]
  public function prefixed_base($base) {
    (new BehindProxy(self::PROXY_ADDRESS))->prefixed($base)->filter(
      $this->proxyRequest('/', ['X-Forwarded-Host' => 'example.com']),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('https://example.com/app/', (string)$request->uri());
  }

  #[@test, @values(['/app', '/app/'])]
  public function stripping_base($base) {
    (new BehindProxy(self::PROXY_ADDRESS))->stripping($base)->filter(
      $this->proxyRequest('/app/', ['X-Forwarded-Host' => 'example.com']),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('https://example.com/', (string)$request->uri());
  }

  #[@test, @values(['/app', '/app/'])]
  public function stripping_base_without_trailing_slash($base) {
    (new BehindProxy(self::PROXY_ADDRESS))->stripping($base)->filter(
      $this->proxyRequest('/app', ['X-Forwarded-Host' => 'example.com']),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('https://example.com/', (string)$request->uri());
  }

  #[@test]
  public function only_uses_last_host() {
    (new BehindProxy(self::PROXY_ADDRESS))->filter(
      $this->proxyRequest('/', ['X-Forwarded-Host' => 'evil.com, example.com']),
      new Response(new TestOutput()),
      new Invocation(function($req, $res) use(&$request) { $request= $req; }, [])
    );

    $this->assertEquals('https://example.com/', (string)$request->uri());
  }
}