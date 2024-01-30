<?php namespace web\unittest\routing;

use test\{Assert, Test, Values};
use web\Request;
use web\io\TestInput;
use web\routing\{RouteMatch, Target};

class TargetTest {

  #[Test, Values([['CONNECT', true], ['POST', false]])]
  public function method($method, $expected) {
    Assert::equals($expected, (new Target('CONNECT', '*'))->matches(new Request(new TestInput($method, '/'))));
  }

  #[Test, Values([['GET', true], ['HEAD', true], ['POST', false]])]
  public function methods($method, $expected) {
    Assert::equals($expected, (new Target(['GET', 'HEAD'], '*'))->matches(new Request(new TestInput($method, '/'))));
  }

  #[Test, Values([['GET', '/test', true], ['GET', '/test/', true], ['GET', '/test/the/west', true], ['GET', '/test.html', false], ['GET', '/TEST', false], ['GET', '/', false], ['POST', '/test', false], ['POST', '/', false]])]
  public function method_and_path($method, $path, $expected) {
    Assert::equals($expected, (new Target('GET', '/test'))->matches(new Request(new TestInput($method, $path))));
  }

  #[Test, Values([[['X-Match' => 'yes'], true], [[], false]])]
  public function on_headers($headers, $expected) {
    $match= new class() implements RouteMatch {
      public function matches($request) { return null !== $request->header('X-Match'); }
    };
    Assert::equals($expected, (new Target('GET', $match))->matches(new Request(new TestInput('GET', '/', $headers))));
  }
}