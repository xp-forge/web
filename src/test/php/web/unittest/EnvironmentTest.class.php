<?php namespace web\unittest;

use io\Path;
use lang\ElementNotFoundException;
use unittest\{Expect, Test, Values};
use util\{Properties, RegisteredPropertySource};
use web\Environment;

class EnvironmentTest extends \unittest\TestCase {

  #[Test]
  public function can_create() {
    new Environment('dev', '.', 'static', []);
  }

  #[Test]
  public function profile() {
    $this->assertEquals('dev', (new Environment('dev', '.', 'static', []))->profile());
  }

  #[Test]
  public function webroot() {
    $this->assertEquals(new Path('.'), (new Environment('dev', '.', 'static', []))->webroot());
  }

  #[Test]
  public function docroot() {
    $this->assertEquals(new Path('static'), (new Environment('dev', '.', 'static', []))->docroot());
  }

  #[Test, Expect(ElementNotFoundException::class)]
  public function non_existant_properties() {
    (new Environment('dev', '.', 'static', []))->properties('inject');
  }

  #[Test]
  public function properties() {
    $prop= new Properties('inject.ini');
    $environment= new Environment('dev', '.', 'static', [new RegisteredPropertySource('inject', $prop)]);
    $this->assertEquals($prop, $environment->properties('inject'));
  }

  #[Test, Values([[[]], [['test', 'value']]])]
  public function arguments($arguments) {
    $this->assertEquals($arguments, (new Environment('dev', '.', 'static', [], $arguments))->arguments());
  }
}