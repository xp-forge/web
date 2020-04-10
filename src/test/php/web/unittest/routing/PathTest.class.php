<?php namespace web\unittest\routing;

use web\Request;
use web\io\TestInput;
use web\routing\Path;

class PathTest extends \unittest\TestCase {

  #[@test, @values([
  #  ['/test', true],
  #  ['/test/', true],
  #  ['/test/the/west', true],
  #  ['/test.html', false],
  #  ['/TEST', false],
  #  ['/not/test', false],
  #  ['/', false]
  #])]
  public function matches($path, $expected) {
    $this->assertEquals($expected, (new Path('/test'))->matches(new Request(new TestInput('GET', $path))));
  }
}