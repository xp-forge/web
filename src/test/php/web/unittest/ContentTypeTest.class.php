<?php namespace web\unittest;

use web\ContentType;
use lang\FormatException;

class ContentTypeTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new ContentType('text/plain');
  }

  #[@test]
  public function media_type() {
    $this->assertEquals('text/plain', (new ContentType('text/plain'))->mediaType());
  }

  #[@test, @values([
  #  [[]],
  #  [['charset' => 'utf-8']],
  #  [['charset' => 'utf-8', 'name' => 'test']]
  #])]
  public function with_params($params) {
    $this->assertEquals($params, (new ContentType('text/plain', $params))->params());
  }

  #[@test]
  public function without_params_in_type() {
    $this->assertEquals([], (new ContentType('text/plain'))->params());
  }

  #[@test, @values([
  #  'text/plain;charset=utf-8',
  #  'text/plain; charset=utf-8',
  #  'text/plain; charset="utf-8"'
  #])]
  public function with_param_in_type($header) {
    $this->assertEquals(['charset' => 'utf-8'], (new ContentType($header))->params());
  }

  #[@test, @values([
  #  'text/plain;charset=utf-8;name=test',
  #  'text/plain; charset=utf-8; name=test',
  #  'text/plain; charset="utf-8"; name="test"'
  #])]
  public function with_multiple_params_in_type($header) {
    $this->assertEquals(['charset' => 'utf-8', 'name' => 'test'], (new ContentType($header))->params());
  }

  #[@test, @values(['charset', 'Charset', 'CHARSET'])]
  public function named_param($name) {
    $this->assertEquals('utf-8', (new ContentType('text/plain', ['charset' => 'utf-8']))->param($name));
  }

  #[@test, @values(['charset', 'Charset', 'CHARSET'])]
  public function named_param_in_type($name) {
    $this->assertEquals('utf-8', (new ContentType('text/plain; charset=utf-8'))->param($name));
  }

  #[@test, @values([
  #  ['name="\"\""', '""'],
  #  ['name="\"T\""', '"T"'],
  #  ['name="\"Test\""', '"Test"'],
  #  ['name="\"Test=Works; really\""', '"Test=Works; really"'],
  #  ['name="\"T\" in the beginning"', '"T" in the beginning'],
  #  ['name="In the end, a \"T\""', 'In the end, a "T"'],
  #  ['name="A \"T\" in the middle"', 'A "T" in the middle'],
  #  ['name="A \"T\" and a \"Q\""', 'A "T" and a "Q"'],
  #  ['name="A \"T!"', 'A "T!'],
  #  ['name="A T\"!"', 'A T"!']
  #])]
  public function quoted_param($params, $expected) {
    $this->assertEquals($expected, (new ContentType('text/plain; '.$params))->param('name'));
  }

  #[@test, @expect(FormatException::class), @values([
  #  'name="',
  #  'name=""; test="',
  #  'name="\"',
  #  'name="\"\"'
  #])]
  public function unclosed_quoted_string($params) {
    new ContentType('text/plain; '.$params);
  }

  #[@test]
  public function named_non_existing_param() {
    $this->assertEquals(null, (new ContentType('text/plain'))->param('charset'));
  }

  #[@test]
  public function named_non_existing_param_with_default() {
    $this->assertEquals('utf-8', (new ContentType('text/plain'))->param('charset', 'utf-8'));
  }

  #[@test, @values([
  #  ['text/plain', true],
  #  ['text/html', false],
  #  ['text/*', true],
  #  ['*/*', true],
  #  ['image/*', false]
  #])]
  public function matches($pattern, $expected) {
    $this->assertEquals($expected, (new ContentType('text/plain; charset=utf-8'))->matches($pattern));
  }

  #[@test]
  public function string_representation() {
    $this->assertEquals(
      'text/plain; charset=utf-8; name="Only a \"test\""',
      (new ContentType('text/plain', ['charset' => 'utf-8', 'name' => 'Only a "test"']))->toString()
    );
  }
}