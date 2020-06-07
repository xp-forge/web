<?php namespace web\unittest;

use unittest\TestCase;
use web\Parameterized;

class ParameterizedTest extends TestCase {

  #[@test]
  public function can_create() {
    new Parameterized('text/plain', []);
  }

  #[@test]
  public function value() {
    $this->assertEquals('text/plain', (new Parameterized('text/plain', []))->value());
  }

  #[@test, @values([
  #  [[]],
  #  [['charset' => 'utf-8']],
  #  [['charset' => 'utf-8', 'level' => '1']],
  #])]
  public function params($params) {
    $this->assertEquals($params, (new Parameterized('text/plain', $params))->params());
  }

  #[@test]
  public function param_by_name() {
    $this->assertEquals('utf-8', (new Parameterized('text/plain', ['charset' => 'utf-8']))->param('charset'));
  }

  #[@test]
  public function non_existant_param() {
    $this->assertNull((new Parameterized('text/plain', ['charset' => 'utf-8']))->param('name'));
  }

  #[@test, @values([null, 'utf-8'])]
  public function non_existant_param_with($default) {
    $this->assertEquals($default, (new Parameterized('text/plain', []))->param('charset', $default));
  }
}