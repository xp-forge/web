<?php namespace web\unittest;

use xp\web\SAPI;
use unittest\TestCase;

class SAPITest extends TestCase {

  #[@test]
  public function can_create() {
    new SAPI();
  }

  #[@test]
  public function http_scheme_default() {
    $this->assertEquals('http', (new SAPI())->scheme());
  }

  #[@test, @values(['on', 'ON', '1'])]
  public function https_scheme_via_https_server_entry($value) {
    $_SERVER['HTTPS']= $value;
    $this->assertEquals('https', (new SAPI())->scheme());
  }

  #[@test, @values(['off', 'OFF', '0'])]
  public function http_scheme_via_https_server_entry($value) {
    $_SERVER['HTTPS']= $value;
    $this->assertEquals('http', (new SAPI())->scheme());
  }

  #[@test, @values(['GET', 'POST', 'OPTIONS'])]
  public function method($value) {
    $_SERVER['REQUEST_METHOD']= $value;
    $this->assertEquals($value, (new SAPI())->method());
  }

  #[@test]
  public function uri() {
    $_SERVER['REQUEST_URI']= '/favicon.ico';
    $this->assertEquals('/favicon.ico', (new SAPI())->uri());
  }

  #[@test]
  public function version() {
    $_SERVER['SERVER_PROTOCOL']= 'HTTP/1.1';
    $this->assertEquals('1.1', (new SAPI())->version());
  }
}