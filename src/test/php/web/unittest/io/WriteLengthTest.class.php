<?php namespace web\unittest\io;

use test\{Assert, Test};
use web\io\{TestOutput, WriteLength};

class WriteLengthTest {

  #[Test]
  public function can_create() {
    new WriteLength(new TestOutput(), 0);
  }

  #[Test]
  public function zero_content_length() {
    $out= new TestOutput();

    $w= new WriteLength($out, 0);
    $w->begin(204, 'No Content', []);

    Assert::equals('Content-Length: 0', $out->headers());
  }

  #[Test]
  public function sets_content_length() {
    $out= new TestOutput();

    $w= new WriteLength($out, 4);
    $w->begin(200, 'OK', []);
    $w->write('Test');

    Assert::equals('Content-Length: 4', $out->headers());
  }

  #[Test]
  public function overwrites_content_length() {
    $out= new TestOutput();

    $w= new WriteLength($out, 4);
    $w->begin(200, 'OK', ['Content-Length' => [6100]]);
    $w->write('Test');

    Assert::equals('Content-Length: 4', $out->headers());
  }

  #[Test]
  public function write_one_chunk() {
    $out= new TestOutput();

    $w= new WriteLength($out, 4);
    $w->begin(200, 'OK', []);
    $w->write('Test');

    Assert::equals('Test', $out->body());
  }

  #[Test]
  public function write_two_small_chunks() {
    $out= new TestOutput();

    $w= new WriteLength($out, 8);
    $w->begin(200, 'OK', []);
    $w->write('Unit');
    $w->write('Test');

    Assert::equals('UnitTest', $out->body());
  }
}