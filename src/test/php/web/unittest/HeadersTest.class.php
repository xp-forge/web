<?php namespace web\unittest;

use lang\FormatException;
use unittest\TestCase;
use web\{Headers, Parameterized};

class HeadersTest extends TestCase {

  #[@test, @values([
  #  'text/plain;charset=utf-8',
  #  'text/plain; charset=utf-8',
  #  'text/plain; charset="utf-8"'
  #])]
  public function content_type($header) {
    $this->assertEquals(
      new Parameterized('text/plain', ['charset' => 'utf-8']),
      Headers::parameterized()->parse($header)
    );
  }

  #[@test, @values([
  #  'attachment;filename=fname.ext',
  #  'attachment; filename=fname.ext',
  #  'attachment; filename="fname.ext"',
  #])]
  public function content_disposition($header) {
    $this->assertEquals(
      new Parameterized('attachment', ['filename' => 'fname.ext']),
      Headers::parameterized()->parse($header)
    );
  }

  #[@test]
  public function accept() {
    $this->assertEquals(
      [
        new Parameterized('text/html', []),
        new Parameterized('application/json', ['q' => '0.9']),
        new Parameterized('*/*', ['q' => '0.8']),
      ],
      Headers::values(Headers::parameterized())->parse('text/html, application/json;q=0.9, */*;q=0.8')
    );
  }

  #[@test]
  public function forwarded() {
    $this->assertEquals(
      [
        ['for' => '192.0.2.60', 'proto' => 'http', 'by' => '203.0.113.43'],
        ['for' => '198.51.100.17'],
      ],
      Headers::values(Headers::pairs())->parse('for=192.0.2.60;proto=http;by=203.0.113.43, for=198.51.100.17')
    );
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
  public function quoted_param($input, $expected) {
    $this->assertEquals(['name' => $expected], Headers::pairs()->parse($input));
  }

  #[@test, @expect(FormatException::class), @values([
  #  'name="',
  #  'name=""; test="',
  #  'name="\"',
  #  'name="\"\"'
  #])]
  public function unclosed_quoted_string($input) {
    Headers::pairs()->parse($input);
  }
}