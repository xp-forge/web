<?php namespace web\unittest;

use lang\FormatException;
use test\{Assert, Expect, Test, Values};
use util\Date;
use web\{Headers, Parameterized};

class HeadersTest {
  const TIMESTAMP = 1621063890;

  #[Test]
  public function date_of_int() {
    Assert::equals('Sat, 15 May 2021 07:31:30 GMT', Headers::date(self::TIMESTAMP));
  }

  #[Test]
  public function date_of_date_instance() {
    Assert::equals('Sat, 15 May 2021 07:31:30 GMT', Headers::date(new Date(self::TIMESTAMP)));
  }

  #[Test, Values(['text/plain;charset=utf-8', 'text/plain; charset=utf-8', 'text/plain; charset="utf-8"'])]
  public function content_type($header) {
    Assert::equals(
      new Parameterized('text/plain', ['charset' => 'utf-8']),
      Headers::parameterized()->parse($header)
    );
  }

  #[Test, Values(['attachment;filename=fname.ext', 'attachment; filename=fname.ext', 'attachment; filename="fname.ext"',])]
  public function content_disposition($header) {
    Assert::equals(
      new Parameterized('attachment', ['filename' => 'fname.ext']),
      Headers::parameterized()->parse($header)
    );
  }

  #[Test, Values(['5;url=http://www.w3.org/pub/WWW/People.html', '5; url=http://www.w3.org/pub/WWW/People.html',])]
  public function refresh($header) {
    Assert::equals(
      new Parameterized('5', ['url' => 'http://www.w3.org/pub/WWW/People.html']),
      Headers::parameterized()->parse($header)
    );
  }

  #[Test]
  public function accept() {
    Assert::equals(
      [
        new Parameterized('text/html', []),
        new Parameterized('application/json', ['q' => '0.9']),
        new Parameterized('*/*', ['q' => '0.8']),
      ],
      Headers::values(Headers::parameterized())->parse('text/html, application/json;q=0.9, */*;q=0.8')
    );
  }

  #[Test]
  public function forwarded() {
    Assert::equals(
      [
        ['for' => '192.0.2.60', 'proto' => 'http', 'by' => '203.0.113.43'],
        ['for' => '198.51.100.17'],
      ],
      Headers::values(Headers::pairs())->parse('for=192.0.2.60;proto=http;by=203.0.113.43, for=198.51.100.17')
    );
  }

  #[Test, Values([['name="\"\""', '""'], ['name="\"T\""', '"T"'], ['name="\"Test\""', '"Test"'], ['name="\"Test=Works; really\""', '"Test=Works; really"'], ['name="\"T\" in the beginning"', '"T" in the beginning'], ['name="In the end, a \"T\""', 'In the end, a "T"'], ['name="A \"T\" in the middle"', 'A "T" in the middle'], ['name="A \"T\" and a \"Q\""', 'A "T" and a "Q"'], ['name="A \"T!"', 'A "T!'], ['name="A T\"!"', 'A T"!']])]
  public function quoted_param($input, $expected) {
    Assert::equals(['name' => $expected], Headers::pairs()->parse($input));
  }

  #[Test, Expect(FormatException::class), Values(['name="', 'name=""; test="', 'name="\"', 'name="\"\"'])]
  public function unclosed_quoted_string($input) {
    Headers::pairs()->parse($input);
  }

  #[Test, Expect(FormatException::class)]
  public function missing_comma() {
    $single= new class() extends Headers {
      protected function next($input, &$offset) {
        return $input[$offset++];
      }
    };
    Headers::values($single)->parse('a@');
  }

  #[Test, Expect(FormatException::class)]
  public function missing_equals() {
    Headers::pairs()->parse('for');
  }
}