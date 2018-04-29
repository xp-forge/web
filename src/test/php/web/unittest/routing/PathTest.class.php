<?php namespace web\unittest\routing;

use web\Request;
use web\routing\Path;
use web\io\TestInput;

class PathTest extends \unittest\TestCase {

  #[@test, @values([
  #  ['/test', ['path' => '/']],
  #  ['/test/', ['path' => '/']],
  #  ['/test/the/west', ['path' => '/the/west']],
  #  ['/test.html', null],
  #  ['/TEST', null],
  #  ['/not/test', null],
  #  ['/', null]
  #])]
  public function matches($path, $expected) {
    $this->assertEquals($expected, (new Path('/test'))->matches(new Request(new TestInput('GET', $path))));
  }
}