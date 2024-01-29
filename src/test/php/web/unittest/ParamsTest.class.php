<?php namespace web\unittest;

use test\{Assert, Before, Test, Values};
use web\io\Param;

class ParamsTest {

  /** @return iterable */
  private function params() {
    yield [Param::parse('key', ['value']), 'value'];
    yield [Param::parse('key[]', ['value']), ['value']];
    yield [Param::parse('key[a]', ['value']), ['a' => 'value']];
    yield [Param::parse('key[a][b]', ['value']), ['a' => ['b' => 'value']]];
  }

  #[Test, Values(from: 'params')]
  public function parse($fixture, $expected) {
    Assert::equals($expected, $fixture->value());
  }

  #[Test]
  public function append_to_empty_params() {
    Assert::equals(
      ['key' => 'value'],
      (Param::from('key', 'value'))->append([])
    );
  }

  #[Test]
  public function append_to_params() {
    Assert::equals(
      ['key' => 'value', 'color' => 'green'],
      (Param::from('key', 'value'))->append(['color' => 'green'])
    );
  }
}