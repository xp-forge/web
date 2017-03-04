<?php namespace web\unittest;

use web\Request;

class RequestTest extends \unittest\TestCase {

  /** @return var[][] */
  private function parameters() {
    return [
      ['fixture=b', 'b'],
      ['fixture[]=b', ['b']],
      ['fixture[][]=b', [['b']]],
      ['fixture=%2F', '/'],
      ['fixture=%2f', '/'],
      ['fixture=%fc', 'ü'],
      ['fixture=%C3', 'Ã'],
      ['fixture=%fc%fc', 'üü'],
      ['fixture=%C3%BC', 'ü'],
    ];
  }

  #[@test]
  public function can_create() {
    new Request(new TestInput('GET', '/'));
  }

  #[@test]
  public function method() {
    $this->assertEquals('GET', (new Request(new TestInput('GET', '/')))->method());
  }

  #[@test]
  public function uri() {
    $this->assertEquals('http://localhost/', (new Request(new TestInput('GET', '/')))->uri()->getURL());
  }

  #[@test]
  public function uri_respects_host_header() {
    $this->assertEquals(
      'http://example.com/',
      (new Request(new TestInput('GET', '/', ['Host' => 'example.com'])))->uri()->getURL()
    );
  }

  #[@test, @values('parameters')]
  public function get_params($query, $expected) {
    $this->assertEquals(
      ['fixture' => $expected],
      (new Request(new TestInput('GET', '/?'.$query, [])))->params()
    );
  }

  #[@test, @values('parameters')]
  public function post_params($query, $expected) {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded', 'Content-Length' => strlen($query)];
    $this->assertEquals(
      ['fixture' => $expected],
      (new Request(new TestInput('POST', '/', $headers, $query)))->params()
    );
  }

  #[@test, @values('parameters')]
  public function post_params_without_content_length($query, $expected) {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded'];
    $this->assertEquals(
      ['fixture' => $expected],
      (new Request(new TestInput('POST', '/', $headers, $query)))->params()
    );
  }

  #[@test]
  public function special_charset_parameter_defined_in_spec() {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded'];
    $this->assertEquals(
      ['fixture' => 'Ã¼'],
      (new Request(new TestInput('POST', '/', $headers, 'fixture=%C3%BC&_charset_=iso-8859-1')))->params()
    );
  }

  #[@test]
  public function charset_in_mediatype_common_nonspec() {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded; charset=iso-8859-1'];
    $this->assertEquals(
      ['fixture' => 'Ã¼'],
      (new Request(new TestInput('POST', '/', $headers, 'fixture=%C3%BC')))->params()
    );
  }

  #[@test, @values('parameters')]
  public function get_param_named($query, $expected) {
    $this->assertEquals($expected, (new Request(new TestInput('GET', '/?'.$query)))->param('fixture'));
  }

  #[@test, @values(['', 'a=b'])]
  public function non_existant_get_param($query) {
    $this->assertEquals(null, (new Request(new TestInput('GET', '/?'.$query)))->param('fixture'));
  }

  #[@test, @values(['', 'a=b'])]
  public function non_existant_get_param_with_default($query) {
    $this->assertEquals('test', (new Request(new TestInput('GET', '/?'.$query)))->param('fixture', 'test'));
  }

  #[@test, @values([
  #  [[]],
  #  [['X-Test' => 'test']],
  #  [['Content-Length' => '6100', 'Content-Type' => 'text/html']]
  #])]
  public function headers($input) {
    $this->assertEquals($input, (new Request(new TestInput('GET', '/', $input)))->headers());
  }

  #[@test, @values(['x-test', 'X-Test', 'X-TEST'])]
  public function header_lookup_is_case_insensitive($lookup) {
    $input= ['X-Test' => 'test'];
    $this->assertEquals('test', (new Request(new TestInput('GET', '/', $input)))->header($lookup));
  }

  #[@test]
  public function non_existant_header() {
    $this->assertEquals(null, (new Request(new TestInput('GET', '/')))->header('X-Test'));
  }

  #[@test]
  public function non_existant_header_with_default() {
    $this->assertEquals('test', (new Request(new TestInput('GET', '/')))->header('X-Test', 'test'));
  }

  #[@test]
  public function non_existant_value() {
    $this->assertEquals(null, (new Request(new TestInput('GET', '/')))->value('test'));
  }

  #[@test]
  public function non_existant_value_with_default() {
    $this->assertEquals('Test', (new Request(new TestInput('GET', '/')))->value('test', 'Test'));
  }

  #[@test]
  public function pass_value() {
    $this->assertEquals($this, (new Request(new TestInput('GET', '/')))->pass('test', $this)->value('test'));
  }

  #[@test]
  public function values() {
    $this->assertEquals([], (new Request(new TestInput('GET', '/')))->values());
  }

  #[@test]
  public function pass_values() {
    $this->assertEquals(['test' => $this], (new Request(new TestInput('GET', '/')))->pass('test', $this)->values());
  }
}