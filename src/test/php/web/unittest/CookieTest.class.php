<?php namespace web\unittest;

use lang\IllegalArgumentException;
use unittest\{Expect, Test, TestCase, Values};
use util\{Date, TimeSpan};
use web\Cookie;

class CookieTest extends TestCase {

  #[Test]
  public function can_create() {
    new Cookie('name', 'value');
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function cannot_create_with_control_character() {
    new Cookie('name', "\x00");
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function cannot_create_with_semicolon() {
    new Cookie('name', ';');
  }

  #[Test]
  public function name() {
    $this->assertEquals('name', (new Cookie('name', 'value'))->name());
  }

  #[Test]
  public function value() {
    $this->assertEquals('value', (new Cookie('name', 'value'))->value());
  }

  #[Test]
  public function attributes() {
    $this->assertEquals(
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
    $this->assertEquals(
      'name=value; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->header()
    );
  }

  #[Test]
  public function removing_http_only() {
    $this->assertEquals(
      'name=value; SameSite=Lax',
      (new Cookie('name', 'value'))->httpOnly(false)->header()
    );
  }

  #[Test]
  public function removing_same_site() {
    $this->assertEquals(
      'name=value; HttpOnly',
      (new Cookie('name', 'value'))->sameSite(null)->header()
    );
  }

  #[Test]
  public function adding_path() {
    $this->assertEquals(
      'name=value; Path=/test; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->path('/test')->header()
    );
  }

  #[Test]
  public function adding_domain() {
    $this->assertEquals(
      'name=value; Domain=.example.com; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->domain('.example.com')->header()
    );
  }

  #[Test]
  public function setting_max_age_to_zero() {
    $this->assertEquals(
      'name=value; Max-Age=0; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->maxAge(0)->header()
    );
  }

  #[Test, Values(eval: '[3600, new TimeSpan(3600)]')]
  public function setting_max_age($value) {
    $this->assertEquals(
      'name=value; Max-Age=3600; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->maxAge($value)->header()
    );
  }

  #[Test, Values(eval: '["Sat, 19 Nov 2016 16:29:22 GMT", new Date("Sat, 19 Nov 2016 16:29:22 GMT"), 1479572962]')]
  public function setting_expiry($value) {
    $this->assertEquals(
      'name=value; Expires=Sat, 19 Nov 2016 16:29:22 GMT; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->expires($value)->header()
    );
  }

  #[Test]
  public function use_null_to_remove() {
    $this->assertEquals(
      'name=; Expires='.gmdate('D, d M Y H:i:s \G\M\T', time() - 86400 * 365).'; Max-Age=0; SameSite=Lax; HttpOnly',
      (new Cookie('name', null))->header()
    );
  }

  #[Test]
  public function setting_secure() {
    $this->assertEquals(
      'name=value; SameSite=Lax; Secure; HttpOnly',
      (new Cookie('name', 'value'))->secure()->header()
    );
  }
}