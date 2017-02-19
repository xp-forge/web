<?php namespace web\unittest;

use web\Request;

class RequestTest extends \unittest\TestCase {

  /** @return var[][] */
  private function parameters() {
    return [
      ['fixture=b', 'b'],
      ['fixture[]=b', ['b']],
      ['fixture[][]=b', [['b']]],
      ['fixture=%2F', '/'],
      ['fixture=%2f', '/'],
      ['fixture=%fc', 'ü'],
      ['fixture=%C3', 'Ã'],
      ['fixture=%fc%fc', 'üü'],
      ['fixture=%C3%BC', 'ü'],
    ];
  }

  #[@test]
  public function can_create() {
    new Request('GET', 'http://localhost/');
  }

  #[@test]
  public function method() {
    $this->assertEquals('GET', (new Request('GET', 'http://localhost/'))->method());
  }

  #[@test]
  public function uri() {
    $this->assertEquals('http://localhost/', (new Request('GET', 'http://localhost/'))->uri()->getURL());
  }

  #[@test, @values('parameters')]
  public function params($query, $expected) {
    $this->assertEquals(['fixture' => $expected], (new Request('GET', 'http://localhost/?'.$query))->params());
  }

  #[@test, @values('parameters')]
  public function param_named($query, $expected) {
    $this->assertEquals($expected, (new Request('GET', 'http://localhost/?'.$query))->param('fixture'));
  }
}