<?php namespace web\unittest\routing;

use web\Request;
use web\routing\Target;
use web\io\TestInput;

class TargetTest extends \unittest\TestCase {

  #[@test, @values([
  #  ['CONNECT', true],
  #  ['POST', null]
  #])]
  public function method($method, $expected) {
    $this->assertEquals($expected, (new Target('CONNECT'))->matches(new Request(new TestInput($method, '/'))));
  }

  #[@test, @values([
  #  ['GET', true],
  #  ['HEAD', true],
  #  ['POST', null]
  #])]
  public function methods($method, $expected) {
    $this->assertEquals($expected, (new Target(['GET', 'HEAD']))->matches(new Request(new TestInput($method, '/'))));
  }

  #[@test, @values([
  #  ['GET', '/test', ['path' => '/']],
  #  ['GET', '/test/', ['path' => '/']],
  #  ['GET', '/test/the/west', ['path' => '/the/west']],
  #  ['GET', '/test.html', null],
  #  ['GET', '/TEST', null],
  #  ['GET', '/', null],
  #  ['POST', '/test', null],
  #  ['POST', '/', null]
  #])]
  public function method_and_path($method, $path, $expected) {
    $this->assertEquals($expected, (new Target('GET', '/test'))->matches(new Request(new TestInput($method, $path))));
  }
}