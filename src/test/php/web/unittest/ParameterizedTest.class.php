<?php namespace web\unittest;

use test\{Assert, Test, Values};
use web\Parameterized;

class ParameterizedTest {

  #[Test]
  public function can_create() {
    new Parameterized('text/plain', []);
  }

  #[Test]
  public function value() {
    Assert::equals('text/plain', (new Parameterized('text/plain', []))->value());
  }

  #[Test, Values([[[]], [['charset' => 'utf-8']], [['charset' => 'utf-8', 'level' => '1']],])]
  public function params($params) {
    Assert::equals($params, (new Parameterized('text/plain', $params))->params());
  }

  #[Test]
  public function param_by_name() {
    Assert::equals('utf-8', (new Parameterized('text/plain', ['charset' => 'utf-8']))->param('charset'));
  }

  #[Test]
  public function non_existant_param() {
    Assert::null((new Parameterized('text/plain', ['charset' => 'utf-8']))->param('name'));
  }

  #[Test, Values([null, 'utf-8'])]
  public function non_existant_param_with($default) {
    Assert::equals($default, (new Parameterized('text/plain', []))->param('charset', $default));
  }
}