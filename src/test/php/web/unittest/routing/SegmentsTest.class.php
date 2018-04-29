<?php namespace web\unittest\routing;

use web\Request;
use web\routing\Segments;
use web\io\TestInput;

class SegmentsTest extends \unittest\TestCase {

  #[@test, @values([
  #  ['/users/1549', ['id' => '1549']],
  #  ['/users/1549/', ['id' => '1549']],
  #  ['/users/1549/avatar', null],
  #  ['/users/', null],
  #  ['/users', null],
  #  ['/', null]
  #])]
  public function matches($path, $expected) {
    $this->assertEquals($expected, (new Segments('/users/{id}'))->matches(new Request(new TestInput('GET', $path))));
  }
}