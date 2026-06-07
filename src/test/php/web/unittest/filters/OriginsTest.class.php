<?php namespace web\unittest\filters;

use lang\IllegalArgumentException;
use test\{Assert, Expect, Test, Values};
use web\filters\Origins;

class OriginsTest {

  #[Test]
  public function can_create() {
    new Origins('http://test');
  }

  #[Test]
  public function localhost() {
    Assert::equals(new Origins(['http://localhost', 'https://localhost']), Origins::localhost());
  }

  #[Test, Values([['http://test', true], ['http://localhost', false], ['http://tests', false], ['', false]])]
  public function matches_origin($origin, $expected) {
    Assert::equals($expected, (new Origins('http://test'))->matches($origin));
  }

  #[Test, Values([['http://test', true], ['https://test', true], ['http://tests', false], ['', false]])]
  public function matches_origins($origin, $expected) {
    Assert::equals($expected, (new Origins(['http://test', 'https://test']))->matches($origin));
  }

  #[Test, Values([['http://test', true], ['http://test:80', false], ['http://locahost', false]])]
  public function matches_no_port($origin, $expected) {
    Assert::equals($expected, (new Origins('http://test'))->matches($origin));
  }

  #[Test, Values([['http://test', false], ['http://test:80', true], ['http://locahost:80', false]])]
  public function matches_specified_port($origin, $expected) {
    Assert::equals($expected, (new Origins('http://test'))->ports([80])->matches($origin));
  }

  #[Test, Values([['http://test', true], ['http://test:80', true], ['http://locahost:80', false]])]
  public function matches_any_port($origin, $expected) {
    Assert::equals($expected, (new Origins('http://test'))->ports('*')->matches($origin));
  }

  #[Test, Values([['http://test', true], ['http://test:80', true], ['http://locahost:80', false]])]
  public function matches_plain_or_specified_port($origin, $expected) {
    Assert::equals($expected, (new Origins('http://test'))->ports([null, 80])->matches($origin));
  }

  #[Test, Values([['http://test:80', true], ['http://test:8080', true], ['http://locahost:80', false]])]
  public function matches_port_range($origin, $expected) {
    Assert::equals($expected, (new Origins('http://test'))->ports([80, '8000..9000'])->matches($origin));
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function illegal_port() {
    (new Origins('http://test'))->ports('invalid');
  }
}