<?php namespace web\unittest;

use io\streams\Streams;
use test\{Assert, Test, Values};
use util\URI;
use web\io\TestInput;
use web\{Request, Session};

class RequestTest {
  use Chunking;

  /** @return var[][] */
  private function parameters() {
    return [
      ['fixture', ''],
      ['fixture=', ''],
      ['fixture=b', 'b'],
      ['fixture[]=b', ['b']],
      ['fixture[][]=b', [['b']]],
      ['fixture[a]=b', ['a' => 'b']],
      ['fixture[0][]=b&fixture[0][]=c', [['b', 'c']]],
      ['fixture=%2F', '/'],
      ['fixture=%2f', '/'],
      ['fixture=%fc', 'ü'],
      ['fixture=%C3', 'Ã'],
      ['fixture=%fc%fc', 'üü'],
      ['fixture=%C3%BC', 'ü'],
    ];
  }

  #[Test]
  public function can_create() {
    new Request(new TestInput('GET', '/'));
  }

  #[Test]
  public function method() {
    Assert::equals('GET', (new Request(new TestInput('GET', '/')))->method());
  }

  #[Test]
  public function uri() {
    Assert::equals(new URI('http://localhost/'), (new Request(new TestInput('GET', '/')))->uri());
  }

  #[Test]
  public function no_session_by_default() {
    Assert::null((new Request(new TestInput('GET', '/')))->session());
  }

  #[Test]
  public function attach_session() {
    $session= new class() extends Session {

      public function id() { return uniqid(); }

      public function register($name, $value) { }

      public function value($name, $default= null) { return null; }

      public function remove($name) { }

      public function destroy() { }
    };
    Assert::equals($session, (new Request(new TestInput('GET', '/')))->attach($session)->session());
  }

  #[Test, Values(eval: '["http://localhost/r", new URI("http://localhost/r")]')]
  public function rewrite_request($uri) {
    Assert::equals(new URI('http://localhost/r'), (new Request(new TestInput('GET', '/')))->rewrite($uri)->uri());
  }

  #[Test, Values(eval: '["/r", new URI("/r")]')]
  public function rewrite_request_relative($uri) {
    Assert::equals(new URI('http://localhost/r'), (new Request(new TestInput('GET', '/')))->rewrite($uri)->uri());
  }

  #[Test, Values([['/', []], ['/?c=test', ['c' => 'test']]])]
  public function parameters_and_rewriting($uri, $expected) {
    $req= new Request(new TestInput('GET', '/?a=b&c=d'));
    $req->params(); // Ensure params are passed from the query string

    Assert::equals($expected, $req->rewrite($uri)->params());
  }

  #[Test]
  public function uri_respects_host_header() {
    Assert::equals(
      'http://example.com/',
      (string)(new Request(new TestInput('GET', '/', ['Host' => 'example.com'])))->uri()
    );
  }

  #[Test, Values(from: 'parameters')]
  public function get_params($query, $expected) {
    Assert::equals(
      ['fixture' => $expected],
      (new Request(new TestInput('GET', '/?'.$query, [])))->params()
    );
  }

  #[Test, Values(from: 'parameters')]
  public function post_params($query, $expected) {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded', 'Content-Length' => strlen($query)];
    Assert::equals(
      ['fixture' => $expected],
      (new Request(new TestInput('POST', '/', $headers, $query)))->params()
    );
  }

  #[Test, Values(from: 'parameters')]
  public function post_params_chunked($query, $expected) {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded'] + self::$CHUNKED;
    Assert::equals(
      ['fixture' => $expected],
      (new Request(new TestInput('POST', '/', $headers, $this->chunked($query, 0xff))))->params()
    );
  }

  #[Test, Values(from: 'parameters')]
  public function post_params_streamed($query, $expected) {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded', 'Transfer-Encoding' => 'streamed'];
    Assert::equals(
      ['fixture' => $expected],
      (new Request(new TestInput('POST', '/', $headers, $query)))->params()
    );
  }

  #[Test]
  public function special_charset_parameter_defined_in_spec() {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded', 'Content-Length' => 35];
    Assert::equals(
      ['fixture' => 'Ã¼'],
      (new Request(new TestInput('POST', '/', $headers, 'fixture=%C3%BC&_charset_=iso-8859-1')))->params()
    );
  }

  #[Test, Values([['%C3%BC', 'ü'], ['%E2%82%AC', '€'], ['%F0%9F%98%80', '😀']])]
  public function multi_byte_sequence($hex, $expected) {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded', 'Content-Length' => 8 + strlen($hex)];
    Assert::equals(
      ['fixture' => $expected],
      (new Request(new TestInput('POST', '/', $headers, 'fixture='.$hex)))->params()
    );
  }

  #[Test]
  public function charset_in_mediatype_common_nonspec() {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded; charset=iso-8859-1', 'Content-Length' => 14];
    Assert::equals(
      ['fixture' => 'Ã¼'],
      (new Request(new TestInput('POST', '/', $headers, 'fixture=%C3%BC')))->params()
    );
  }

  #[Test, Values(from: 'parameters')]
  public function get_param_named($query, $expected) {
    Assert::equals($expected, (new Request(new TestInput('GET', '/?'.$query)))->param('fixture'));
  }

  #[Test, Values(['', 'a=b'])]
  public function non_existant_get_param($query) {
    Assert::equals(null, (new Request(new TestInput('GET', '/?'.$query)))->param('fixture'));
  }

  #[Test, Values(['', 'a=b'])]
  public function non_existant_get_param_with_default($query) {
    Assert::equals('test', (new Request(new TestInput('GET', '/?'.$query)))->param('fixture', 'test'));
  }

  #[Test, Values([[[]], [['X-Test' => 'test']], [['Content-Length' => '6100', 'Content-Type' => 'text/html']]])]
  public function headers($input) {
    Assert::equals($input, (new Request(new TestInput('GET', '/', $input)))->headers());
  }

  #[Test, Values([[['Accept' => ['application/vnd.api+json', 'image/png']]], [['Accept' => 'application/vnd.api+json', 'accept' => 'image/png']]])]
  public function multiple_headers($input) {
    Assert::equals(
      'application/vnd.api+json, image/png',
      (new Request(new TestInput('GET', '/', $input)))->header('Accept')
    );
  }

  #[Test, Values(['x-test', 'X-Test', 'X-TEST'])]
  public function header_lookup_is_case_insensitive($lookup) {
    $input= ['X-Test' => 'test'];
    Assert::equals('test', (new Request(new TestInput('GET', '/', $input)))->header($lookup));
  }

  #[Test]
  public function non_existant_header() {
    Assert::equals(null, (new Request(new TestInput('GET', '/')))->header('X-Test'));
  }

  #[Test]
  public function non_existant_header_with_default() {
    Assert::equals('test', (new Request(new TestInput('GET', '/')))->header('X-Test', 'test'));
  }

  #[Test]
  public function non_existant_value() {
    Assert::equals(null, (new Request(new TestInput('GET', '/')))->value('test'));
  }

  #[Test]
  public function non_existant_value_with_default() {
    Assert::equals('Test', (new Request(new TestInput('GET', '/')))->value('test', 'Test'));
  }

  #[Test]
  public function inject_value() {
    Assert::equals($this, (new Request(new TestInput('GET', '/')))->pass('test', $this)->value('test'));
  }

  #[Test]
  public function values() {
    Assert::equals([], (new Request(new TestInput('GET', '/')))->values());
  }

  #[Test]
  public function inject_values() {
    Assert::equals(['test' => $this], (new Request(new TestInput('GET', '/')))->pass('test', $this)->values());
  }

  #[Test]
  public function no_cookies() {
    Assert::equals([], (new Request(new TestInput('GET', '/', [])))->cookies());
  }

  #[Test]
  public function cookies() {
    Assert::equals(
      ['user' => 'thekid', 'tz' => 'Europe/Berlin'],
      (new Request(new TestInput('GET', '/', ['Cookie' => 'user=thekid; tz=Europe%2FBerlin'])))->cookies()
    );
  }

  #[Test]
  public function non_existant_cookie() {
    Assert::equals(null, (new Request(new TestInput('GET', '/', [])))->cookie('user'));
  }

  #[Test]
  public function non_existant_cookie_with_guest() {
    Assert::equals('guest', (new Request(new TestInput('GET', '/', [])))->cookie('user', 'guest'));
  }

  #[Test]
  public function cookie() {
    Assert::equals(
      'Europe/Berlin',
      (new Request(new TestInput('GET', '/', ['Cookie' => 'user=thekid; tz=Europe%2FBerlin'])))->cookie('tz')
    );
  }

  #[Test]
  public function cookie_value_decoded() {
    Assert::equals(
      '"valüe" with spaces',
      (new Request(new TestInput('GET', '/', ['Cookie' => 'test=%22val%C3%BCe%22%20with%20spaces'])))->cookie('test')
    );
  }

  #[Test, Values([0, 8192, 10000])]
  public function stream_with_content_length($length) {
    $body= str_repeat('A', $length);
    Assert::equals(
      $body,
      Streams::readAll((new Request(new TestInput('GET', '/', ['Content-Length' => $length], $body)))->stream())
    );
  }

  #[Test, Values([0, 8190, 10000])]
  public function form_encoded_payload($length) {
    $body= 'a='.str_repeat('A', $length);
    $headers= ['Content-Length' => $length + 2, 'Content-Type' => 'application/x-www-form-urlencoded'];
    Assert::equals(
      $body,
      Streams::readAll((new Request(new TestInput('GET', '/', $headers, $body)))->stream())
    );
  }

  #[Test, Values([0, 8180, 10000])]
  public function chunked_payload($length) {
    $transfer= sprintf("5\r\nHello\r\n1\r\n \r\n%x\r\n%s\r\n0\r\n\r\n", $length, str_repeat('A', $length));
    Assert::equals(
      'Hello '.str_repeat('A', $length),
      Streams::readAll((new Request(new TestInput('GET', '/', self::$CHUNKED, $transfer)))->stream())
    );
  }

  #[Test]
  public function consume_without_data() {
    $req= new Request(new TestInput('GET', '/', []));
    Assert::equals(-1, $req->consume());
  }

  #[Test]
  public function consume_length() {
    $req= new Request(new TestInput('GET', '/', ['Content-Length' => 100], str_repeat('A', 100)));
    Assert::equals(100, $req->consume());
  }

  #[Test]
  public function consume_length_after_partial_read() {
    $req= new Request(new TestInput('GET', '/', ['Content-Length' => 100], str_repeat('A', 100)));
    $partial= $req->stream()->read(50);
    Assert::equals(100 - strlen($partial), $req->consume());
  }

  #[Test]
  public function consume_chunked() {
    $req= new Request(new TestInput('GET', '/', self::$CHUNKED, $this->chunked(str_repeat('A', 100))));
    Assert::equals(100, $req->consume());
  }

  #[Test]
  public function consume_chunked_after_partial_read() {
    $req= new Request(new TestInput('GET', '/', self::$CHUNKED, $this->chunked(str_repeat('A', 100))));
    $partial= $req->stream()->read(50);
    Assert::equals(100 - strlen($partial), $req->consume());
  }

  #[Test]
  public function string_representation() {
    $req= new Request(new TestInput('GET', '/', ['Host' => 'localhost', 'Connection' => 'close']));
    Assert::equals(
      "web.Request(GET util.URI<http://localhost/>)@[\n".
      "  Host => [\"localhost\"]\n".
      "  Connection => [\"close\"]\n".
      "]",
      $req->toString()
    );
  }

  #[Test]
  public function hash_code() {
    $req= new Request(new TestInput('GET', '/', ['Host' => 'localhost', 'Connection' => 'close']));
    Assert::equals(spl_object_hash($req), $req->hashCode());
  }

  #[Test]
  public function form_encoded_without_payload() {
    $headers= ['Content-Type' => 'application/x-www-form-urlencoded'];
    Assert::equals(
      ['source' => 'query'],
      (new Request(new TestInput('DELETE', '/?source=query', $headers)))->params()
    );
  }
}