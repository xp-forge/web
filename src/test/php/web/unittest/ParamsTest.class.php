<?php namespace web\unittest;

use lang\FormatException;
use test\{Assert, Before, Expect, Test, Values};
use web\io\Param;

class ParamsTest {

  /** @return iterable */
  private function params() {
    yield ['key', 'value'];
    yield ['key[]', ['value']];
    yield ['key[][]', [['value']]];
    yield ['key[0]', ['value']];
    yield ['key[0][a]', [['a' => 'value']]];
    yield ['key[a]', ['a' => 'value']];
    yield ['key[a][b]', ['a' => ['b' => 'value']]];
    yield ['key[%C3%BC]', ['ü' => 'value']];
    yield ['key[%C3%BC]', ['ü' => 'value']];
  }

  /** @return iterable */
  private function merge() {
    yield [[
      ['key', 'value'],
      ['color', 'green'],
      ['price', '12.99'],
    ]];
    yield [[
      ['key.name', 'value'],
      ['color name', 'green'],
      [' price', '12.99'],
      ['gr%c3%bcn', 'de'],
      ['de', 'gr%c3%bcn'],
    ]];
    yield [[
      ['accepted', 'true'],
      ['accepted', 'false'],
      ['accepted', 'null'],
    ]];
    yield [[
      ['accepted[]', 'true'],
      ['accepted[]', 'false'],
      ['accepted[]', 'null'],
    ]];
    yield [[
      ['accepted[][a]', 'true'],
      ['accepted[][b]', 'false'],
      ['accepted[][c]', 'null'],
    ]];
    yield [[
      ['accepted[tc]', 'true'],
      ['accepted[legal]', 'true'],
      ['accepted[pay]', 'true'],
    ]];
    yield [[
      ['accepted[user][]', 'tc'],
      ['accepted[user][]', 'legal'],
      ['accepted[user][]', 'pay'],
    ]];
    yield [[
      ['accepted[user][tc]', 'true'],
      ['accepted[user][legal]', 'true'],
      ['accepted[user][pay]', 'true'],
    ]];
    yield [[
      ['access[0][user]', 'true'],
      ['access[0][files]', 'true'],
      ['access[0][calendar]', 'true'],
      ['access[1][wiki]', 'true'],
    ]];
    yield [[
      ['access[0][]', 'user'],
      ['access[0][]', 'files'],
      ['access[0][]', 'calendar'],
      ['access[1][]', 'wiki'],
    ]];
  }

  #[Test, Values(from: 'params')]
  public function parse($key, $expected) {
    Assert::equals($expected, Param::parse($key, ['value'])->value());
  }

  #[Test]
  public function missing_closing_bracket() {
    Assert::equals(['value'], Param::parse('key[', ['value'])->value());
  }

  #[Test, Expect(class: FormatException::class, message: '/Cannot parse key.+/')]
  public function max_input_nesting_level() {
    Param::parse('key'.str_repeat('[]', ini_get('max_input_nesting_level') + 1), ['value']);
  }

  #[Test, Values(from: 'merge')]
  public function merging_consistent_with_parse_str($params) {
    $outcome= [];
    $string= '';
    foreach ($params as list($param, $value)) {
      $string.= '&'.$param.'='.$value;

      $parsed= Param::parse($param, [$value]);
      $parsed->merge($outcome[$parsed->name()]);
    }

    parse_str(substr($string, 1), $expected);
    Assert::equals($expected, $outcome);
  }

  #[Test]
  public function encoded_brackets_in_offset() {

    // IMO this should be ["[]" => "value"] but we'll be consistent with PHP here
    Assert::equals(['[' => ['value']], Param::parse('key[%5B%5D]', ['value'])->value());
  }
}