<?php namespace web\unittest\filters;

use test\{Assert, Test, Values};
use web\filters\{CORS, Invocation};
use web\io\{TestInput, TestOutput};
use web\{Filter, Request, Response};

class CORSTest {
  const ORIGIN= 'http://example.com';
  const RESPONSE= ['Content-Type' => 'text/plain', 'Content-Length' => 9];

  private function filter(CORS $fixture, $method, $uri, $headers= [], $body= null) {
    $req= new Request(new TestInput($method, $uri, $headers, $body ?? ''));
    $res= new Response(new TestOutput());
    $fixture->filter($req, $res, new Invocation(function($req, $res) {
      $res->send('Completed', 'text/plain');
    }));
    return $res;
  }

  /** Returns fixture with the origin set */
  private function fixture(): CORS {
    return (new CORS())->origins(self::ORIGIN);
  }

  /** Values for preflight test */
  private function preflights(): iterable {
    yield [$this->fixture(), []];
    yield [$this->fixture()->origins(fn($origin) => self::ORIGIN === $origin ? $origin : null), []];
    yield [$this->fixture()->origins('*'), ['Access-Control-Allow-Origin' => '*']];

    // Methods
    yield [$this->fixture()->methods(null), []];
    yield [$this->fixture()->methods([]), []];
    yield [$this->fixture()->methods('GET, POST'), ['Access-Control-Allow-Methods'  => 'GET, POST']];
    yield [$this->fixture()->methods(['GET', 'POST']), ['Access-Control-Allow-Methods'  => 'GET, POST']];

    // Headers
    yield [$this->fixture()->headers(null), []];
    yield [$this->fixture()->headers([]), []];
    yield [$this->fixture()->headers('X-Input'), ['Access-Control-Allow-Headers'  => 'X-Input']];
    yield [$this->fixture()->headers(['X-Input']), ['Access-Control-Allow-Headers'  => 'X-Input']];

    // Age
    yield [$this->fixture()->maxAge(null), []];
    yield [$this->fixture()->maxAge(0), []];
    yield [$this->fixture()->maxAge(86400), ['Access-Control-Max-Age'  => '86400']];

    // Expose
    yield [$this->fixture()->expose(null), []];
    yield [$this->fixture()->expose([]), []];
    yield [$this->fixture()->expose('X-Output'), ['Access-Control-Expose-Headers'  => 'X-Output']];
    yield [$this->fixture()->expose(['X-Output']), ['Access-Control-Expose-Headers'  => 'X-Output']];

    // Credentials
    yield [$this->fixture()->credentials(false), []];
    yield [$this->fixture()->credentials(true), ['Access-Control-Allow-Credentials'  => 'true']];
  }

  /** Values for request test */
  private function requests(): iterable {
    yield [$this->fixture(), []];

    // Only included in preflight
    yield [$this->fixture()->methods(['GET', 'POST']), []];
    yield [$this->fixture()->headers(['X-Input']), []];
    yield [$this->fixture()->maxAge(86400), []];

    // Included in all requests
    yield [$this->fixture()->expose(['X-Output']), ['Access-Control-Expose-Headers'  => 'X-Output']];
    yield [$this->fixture()->credentials(true), ['Access-Control-Allow-Credentials'  => 'true']];
  }

  /** Values for allowing_origin_with_any_4_digit_port */
  private function origins(): iterable {
    yield [self::ORIGIN, true];
    yield [self::ORIGIN.':3000', true];

    // Not allowed
    yield [self::ORIGIN.':443', false];
    yield [strtr(self::ORIGIN, ['http:' => 'https:']), false];
    yield ['http://localhost', false];
    yield ['', false];
  }

  #[Test]
  public function can_create() {
    new CORS();
  }

  #[Test]
  public function request_without_origin_receives_no_cors() {
    $response= $this->filter(new CORS(), 'GET', '/');

    Assert::equals(200, $response->status());
    Assert::equals(self::RESPONSE, $response->headers());
  }

  #[Test, Values(from: 'preflights')]
  public function preflight($fixture, $expected) {
    $response= $this->filter($fixture, 'OPTIONS', '/',  [
      'Origin'                         => self::ORIGIN,
      'Access-Control-Request-Method'  => 'GET',
      'Access-Control-Request-Headers' => 'X-Input',
    ]);

    Assert::equals(204, $response->status());
    Assert::equals(
      $expected + ['Vary' => 'Origin', 'Access-Control-Allow-Origin' => self::ORIGIN],
      $response->headers()
    );
  }

  #[Test, Values(from: 'requests')]
  public function request($fixture, $expected) {
    $response= $this->filter($fixture, 'GET', '/', ['Origin' => self::ORIGIN]);

    Assert::equals(200, $response->status());
    Assert::equals(
      $expected + ['Vary' => 'Origin', 'Access-Control-Allow-Origin' => self::ORIGIN] + self::RESPONSE,
      $response->headers()
    );
  }

  #[Test, Values(from: 'origins')]
  public function allowing_origin_with_any_4_digit_port($origin, $allow) {
    $fixture= (new CORS())->origins(function($origin) {
      return preg_match('/^'.preg_quote(self::ORIGIN, '/').'(:[0-9]{4})?$/', $origin) ? $origin : null;
    });
    $response= $this->filter($fixture, 'GET', '/', ['Origin' => $origin]);

    Assert::equals(200, $response->status());
    Assert::equals(
      ($allow ? ['Access-Control-Allow-Origin' => $origin] : []) + ['Vary' => 'Origin'] + self::RESPONSE,
      $response->headers()
    );
  }
}