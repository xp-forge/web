<?php namespace web\unittest\logging;

use test\{Assert, Test};
use util\log\{BufferedAppender, LogCategory};
use web\io\{TestInput, TestOutput};
use web\logging\ToCategory;
use web\{Error, Request, Response};

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
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $buffered= new BufferedAppender();
    (new ToCategory((new LogCategory('test'))->withAppender($buffered)))->log($req, $res, null);

    Assert::notEquals(0, strlen($buffered->getBuffer()));
  }

  #[Test]
  public function log_with_error() {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $buffered= new BufferedAppender();
    (new ToCategory((new LogCategory('test'))->withAppender($buffered)))->log($req, $res, new Error(404, 'Test'));

    Assert::notEquals(0, strlen($buffered->getBuffer()));
  }
}