<?php namespace web\unittest;

use web\Request;
use io\streams\Streams;

class RequestTest extends \unittest\TestCase {
  private static $CHUNKED = ['Transfer-Encoding' => 'chunked'];

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
    $this->assertEquals('http://localhost/', (string)(new Request(new TestInput('GET', '/')))->uri());
  }

  #[@test]
  public function uri_respects_host_header() {
    $this->assertEquals(
      'http://example.com/',
      (string)(new Request(new TestInput('GET', '/', ['Host' => 'example.com'])))->uri()
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
  public function post_params_chunked($query, $expected) {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded', 'Transfer-Encoding' => 'chunked'];
    $this->assertEquals(
      ['fixture' => $expected],
      (new Request(new TestInput('POST', '/', $headers, sprintf("%x\r\n%s\r\n0\r\n\r\n", strlen($query), $query))))->params()
    );
  }

  #[@test]
  public function special_charset_parameter_defined_in_spec() {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded', 'Content-Length' => 35];
    $this->assertEquals(
      ['fixture' => 'Ã¼'],
      (new Request(new TestInput('POST', '/', $headers, 'fixture=%C3%BC&_charset_=iso-8859-1')))->params()
    );
  }

  #[@test]
  public function charset_in_mediatype_common_nonspec() {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded; charset=iso-8859-1', 'Content-Length' => 14];
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

  #[@test, @values([
  #  [['Accept' => ['application/vnd.api+json', 'image/png']]],
  #  [['Accept' => 'application/vnd.api+json', 'accept' => 'image/png']]
  #])]
  public function multiple_headers($input) {
    $this->assertEquals(
      'application/vnd.api+json, image/png',
      (new Request(new TestInput('GET', '/', $input)))->header('Accept')
    );
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

  #[@test]
  public function no_cookies() {
    $this->assertEquals([], (new Request(new TestInput('GET', '/', [])))->cookies());
  }

  #[@test]
  public function cookies() {
    $this->assertEquals(
      ['user' => 'thekid', 'tz' => 'Europe/Berlin'],
      (new Request(new TestInput('GET', '/', ['Cookie' => 'user=thekid; tz=Europe%2FBerlin'])))->cookies()
    );
  }

  #[@test]
  public function non_existant_cookie() {
    $this->assertEquals(null, (new Request(new TestInput('GET', '/', [])))->cookie('user'));
  }

  #[@test]
  public function non_existant_cookie_with_guest() {
    $this->assertEquals('guest', (new Request(new TestInput('GET', '/', [])))->cookie('user', 'guest'));
  }

  #[@test]
  public function cookie() {
    $this->assertEquals(
      'Europe/Berlin',
      (new Request(new TestInput('GET', '/', ['Cookie' => 'user=thekid; tz=Europe%2FBerlin'])))->cookie('tz')
    );
  }

  #[@test, @values([0, 8192, 10000])]
  public function stream_with_content_length($length) {
    $body= str_repeat('A', $length);
    $this->assertEquals(
      $body,
      Streams::readAll((new Request(new TestInput('GET', '/', ['Content-Length' => $length], $body)))->stream())
    );
  }

  #[@test, @values([0, 8190, 10000])]
  public function form_encoded_payload($length) {
    $body= 'a='.str_repeat('A', $length);
    $headers= ['Content-Length' => $length + 2, 'Content-Type' => 'application/x-www-form-urlencoded'];
    $this->assertEquals(
      $body,
      Streams::readAll((new Request(new TestInput('GET', '/', $headers, $body)))->stream())
    );
  }

  #[@test, @values([0, 8180, 10000])]
  public function chunked_payload($length) {
    $transfer= sprintf("5\r\nHello\r\n1\r\n \r\n%x\r\n%s\r\n0\r\n\r\n", $length, str_repeat('A', $length));
    $this->assertEquals(
      'Hello '.str_repeat('A', $length),
      Streams::readAll((new Request(new TestInput('GET', '/', self::$CHUNKED, $transfer)))->stream())
    );
  }

  #[@test]
  public function consume_without_data() {
    $req= new Request(new TestInput('GET', '/', [], null));
    $this->assertEquals(-1, $req->consume());
  }

  #[@test]
  public function consume_length() {
    $req= new Request(new TestInput('GET', '/', ['Content-Length' => 100], str_repeat('A', 100)));
    $this->assertEquals(100, $req->consume());
  }

  #[@test]
  public function consume_length_after_partial_read() {
    $req= new Request(new TestInput('GET', '/', ['Content-Length' => 100], str_repeat('A', 100)));
    $partial= $req->stream()->read(50);
    $this->assertEquals(100 - strlen($partial), $req->consume());
  }

  #[@test]
  public function consume_chunked() {
    $req= new Request(new TestInput('GET', '/', self::$CHUNKED, "64\r\n".str_repeat('A', 100)."\r\n0\r\n\r\n"));
    $this->assertEquals(100, $req->consume());
  }

  #[@test]
  public function consume_chunked_after_partial_read() {
    $req= new Request(new TestInput('GET', '/', self::$CHUNKED, "64\r\n".str_repeat('A', 100)."\r\n0\r\n\r\n"));
    $partial= $req->stream()->read(50);
    $this->assertEquals(100 - strlen($partial), $req->consume());
  }
}