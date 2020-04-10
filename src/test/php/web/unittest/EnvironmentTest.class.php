<?php namespace web\unittest;

use io\Path;
use lang\ElementNotFoundException;
use util\{Properties, RegisteredPropertySource};
use web\Environment;

class EnvironmentTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Environment('dev', '.', 'static', []);
  }

  #[@test]
  public function profile() {
    $this->assertEquals('dev', (new Environment('dev', '.', 'static', []))->profile());
  }

  #[@test]
  public function webroot() {
    $this->assertEquals(new Path('.'), (new Environment('dev', '.', 'static', []))->webroot());
  }

  #[@test]
  public function docroot() {
    $this->assertEquals(new Path('static'), (new Environment('dev', '.', 'static', []))->docroot());
  }

  #[@test, @expect(ElementNotFoundException::class)]
  public function non_existant_properties() {
    (new Environment('dev', '.', 'static', []))->properties('inject');
  }

  #[@test]
  public function properties() {
    $prop= new Properties('inject.ini');
    $environment= new Environment('dev', '.', 'static', [new RegisteredPropertySource('inject', $prop)]);
    $this->assertEquals($prop, $environment->properties('inject'));
  }

  #[@test, @values([[[]], [['test', 'value']]])]
  public function arguments($arguments) {
    $this->assertEquals($arguments, (new Environment('dev', '.', 'static', [], $arguments))->arguments());
  }
}