<?php namespace web\unittest;

use lang\IllegalArgumentException;
use test\{Assert, Test};
use web\InternalServerError;

class InternalServerErrorTest {

  #[Test]
  public function can_create() {
    new InternalServerError(new IllegalArgumentException('Test'));
  }

  #[Test]
  public function uses_error_500() {
    $e= new InternalServerError(new IllegalArgumentException('Test'));
    Assert::equals(500, $e->status());
  }
}