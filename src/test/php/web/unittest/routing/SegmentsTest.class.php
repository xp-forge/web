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

  #[@test, @values([
  #  ['/users/1549', ['id' => '1549']],
  #  ['/users/1549/', ['id' => '1549']],
  #  ['/users/friebe', null]
  #])]
  public function matches_with_pattern($path, $expected) {
    $this->assertEquals($expected, (new Segments('/users/{id:[0-9]+}'))->matches(new Request(new TestInput('GET', $path))));
  }

  #[@test]
  public function blog_article_usecase() {
    $segments= new Segments('/blog/{cat}/{id:[0-9]+}(-{slug:.+})?');
    $this->assertEquals(
      ['cat' => 'development', 'id' => '1'],
      $segments->matches(new Request(new TestInput('GET', '/blog/development/1')))
    );
  }
}