<?php namespace web\unittest\routing;

use web\Request;
use web\routing\Segments;
use web\io\TestInput;

class SegmentsTest extends \unittest\TestCase {

  #[@test, @values([
  #  ['/users/1549', true],
  #  ['/users/1549/', true],
  #  ['/users/1549/avatar', false],
  #  ['/users/', false],
  #  ['/users', false],
  #  ['/', false]
  #])]
  public function matches($path, $expected) {
    $this->assertEquals($expected, (new Segments('/users/{id}'))->matches(new Request(new TestInput('GET', $path))));
  }
}