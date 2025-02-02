<?php namespace web\unittest\logging;

use test\{Assert, Test};
use util\log\{BufferedAppender, LogCategory};
use web\Error;
use web\logging\ToCategory;

class ToCategoryTest {

  #[Test]
  public function can_create() {
    new ToCategory(new LogCategory('test'));
  }

  #[Test]
  public function target() {
    $cat= new LogCategory('test');
    Assert::equals('web.logging.ToCategory('.$cat->toString().')', (new ToCategory($cat))->target());
  }

  #[Test]
  public function log() {
    $buffered= new BufferedAppender();
    (new ToCategory((new LogCategory('test'))->withAppender($buffered)))->log(200, 'GET', '/', []);

    Assert::notEquals(0, strlen($buffered->getBuffer()));
  }

  #[Test]
  public function log_with_error() {
    $buffered= new BufferedAppender();
    (new ToCategory((new LogCategory('test'))->withAppender($buffered)))->log(
      404,
      'GET',
      '/not-found',
      ['error' => new Error(404, 'Test')]
    );

    Assert::notEquals(0, strlen($buffered->getBuffer()));
  }
}