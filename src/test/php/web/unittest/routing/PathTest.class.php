<?php namespace web\unittest\routing;

use web\Request;
use web\routing\Path;

class PathTest extends \unittest\TestCase {
  const BASE = 'http://test.example.com';

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
    $this->assertEquals($expected, (new Path('/test'))->matches(new Request('GET', self::BASE.$path)));
  }
}