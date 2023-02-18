<?php namespace web\unittest;

use lang\IllegalArgumentException;
use test\{Assert, Expect, Test, Values};
use util\{Date, TimeSpan};
use web\{Cookie, Headers};

class CookieTest {

  #[Test]
  public function can_create() {
    new Cookie('name', 'value');
  }

  #[Test]
  public function name() {
    Assert::equals('name', (new Cookie('name', 'value'))->name());
  }

  #[Test, Expect(IllegalArgumentException::class), Values(["=", ",", ";", " ", "\t", "\r", "\n", "\013", "\014"])]
  public function name_cannot_contain($char) {
    new Cookie('name'.$char, 'value');
  }

  #[Test]
  public function value() {
    Assert::equals('value', (new Cookie('name', 'value'))->value());
  }

  #[Test]
  public function attributes() {
    Assert::equals(
      [
        'expires'  => null,
        'maxAge'   => null,
        'path'     => null,
        'domain'   => null,
        'secure'   => false,
        'httpOnly' => true,
        'sameSite' => 'Lax',
      ],
      (new Cookie('name', 'value'))->attributes()
    );
  }

  #[Test]
  public function http_only_and_same_site_per_default() {
    Assert::equals(
      'name=value; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->header()
    );
  }

  #[Test]
  public function removing_http_only() {
    Assert::equals(
      'name=value; SameSite=Lax',
      (new Cookie('name', 'value'))->httpOnly(false)->header()
    );
  }

  #[Test]
  public function removing_same_site() {
    Assert::equals(
      'name=value; HttpOnly',
      (new Cookie('name', 'value'))->sameSite(null)->header()
    );
  }

  #[Test]
  public function adding_path() {
    Assert::equals(
      'name=value; Path=/test; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->path('/test')->header()
    );
  }

  #[Test]
  public function adding_domain() {
    Assert::equals(
      'name=value; Domain=.example.com; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->domain('.example.com')->header()
    );
  }

  #[Test]
  public function characters_in_value_get_encoded() {
    Assert::equals(
      'name=%22val%C3%BCe%22%20with%20spaces; SameSite=Lax; HttpOnly',
      (new Cookie('name', '"valüe" with spaces'))->header()
    );
  }

  #[Test]
  public function control_character_in_value_gets_encoded() {
    Assert::equals(
      'name=a%00; SameSite=Lax; HttpOnly',
      (new Cookie('name', "a\0"))->header()
    );
  }

  #[Test]
  public function semicolon_in_value_gets_encoded() {
    Assert::equals(
      'name=a%3B; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'a;'))->header()
    );
  }

  #[Test]
  public function setting_max_age_to_zero() {
    Assert::equals(
      'name=value; Max-Age=0; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->maxAge(0)->header()
    );
  }

  #[Test, Values(eval: '[3600, new TimeSpan(3600)]')]
  public function setting_max_age($value) {
    Assert::equals(
      'name=value; Max-Age=3600; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->maxAge($value)->header()
    );
  }

  #[Test, Values(eval: '["Sat, 19 Nov 2016 16:29:22 GMT", new Date("Sat, 19 Nov 2016 16:29:22 GMT"), 1479572962]')]
  public function setting_expiry($value) {
    Assert::equals(
      'name=value; Expires=Sat, 19 Nov 2016 16:29:22 GMT; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->expires($value)->header()
    );
  }

  #[Test]
  public function use_null_to_remove() {
    Assert::equals(
      'name=; Expires='.Headers::date(time() - 86400 * 365).'; Max-Age=0; SameSite=Lax; HttpOnly',
      (new Cookie('name', null))->header()
    );
  }

  #[Test]
  public function setting_secure() {
    Assert::equals(
      'name=value; SameSite=Lax; Secure; HttpOnly',
      (new Cookie('name', 'value'))->secure()->header()
    );
  }

  #[Test]
  public function string_representation() {
    Assert::equals(
      'web.Cookie<name=value; SameSite=Lax; HttpOnly>',
      (new Cookie('name', 'value'))->toString()
    );
  }

  #[Test]
  public function hash_code() {
    Assert::equals(705299525, (new Cookie('name', 'value'))->hashCode());
  }

  #[Test]
  public function compare_with_same_name_and_value() {
    Assert::equals(0, (new Cookie('name', 'value'))->compareTo(new Cookie('name', 'value')));
  }

  #[Test]
  public function compare_with_different_value() {
    Assert::equals(1, (new Cookie('name', 'value'))->compareTo(new Cookie('name', 'other')));
  }
}