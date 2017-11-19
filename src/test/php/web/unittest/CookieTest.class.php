<?php namespace web\unittest;

use web\Cookie;

class CookieTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Cookie('name', 'value');
  }

  #[@test]
  public function http_only_and_same_site_per_default() {
    $this->assertEquals(
      'name=value; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->header()
    );
  }

  #[@test]
  public function adding_path() {
    $this->assertEquals(
      'name=value; Path=/test; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->path('/test')->header()
    );
  }

  #[@test]
  public function setting_expiry() {
    $this->assertEquals(
      'name=value; Expires=Sat, 19 Nov 2016 16:29:22 GMT; SameSite=Lax; HttpOnly',
      (new Cookie('name', 'value'))->expires('Sat, 19 Nov 2016 16:29:22 GMT')->header()
    );
  }

  #[@test]
  public function use_null_to_remove() {
    $this->assertEquals(
      'name=; Expires='.gmdate('D, d M Y H:i:s \G\M\T', time() - 86400 * 365).'; Max-Age=0; SameSite=Lax; HttpOnly',
      (new Cookie('name', null))->header()
    );
  }
}