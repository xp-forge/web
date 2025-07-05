<?php namespace web\unittest;

use peer\ProtocolException;
use test\{Assert, After, Expect, Test, Values};
use util\Bytes;
use websocket\WebSocket;

#[StartServer(TestingServer::class)]
class IntegrationTest {
  const FORM_URLENCODED= 'application/x-www-form-urlencoded';

  private $server;

  /** @param web.unittest.StartServer $server */
  public function __construct($server) {
    $this->server= $server;
  }

  /** @return iterable */
  private function messages() {
    yield ['Test', 'Echo: Test'];
    yield [new Bytes([8, 15]), new Bytes([47, 11, 8, 15])];
  }

  /**
   * Sends a request. Opens connection before sending the request, and closes
   * connection after reading the response.
   *
   * @param  string $method
   * @param  string $uri
   * @param  string $version
   * @param  [:string] $headers
   * @param  string $body
   * @return void
   */
  private function send($method, $uri, $version= '1.0', $headers= [], $body= '') {
    $this->server->connection->connect();
    try {

      // Send request. Ensure `Connection: close` is always sent along in order to
      // be able to read until EOF instead of having to parse the response payload.
      $this->server->connection->write($method.' '.$uri.' HTTP/'.$version."\r\n");
      foreach (['Connection' => 'close'] + $headers as $name => $value) {
        $this->server->connection->write($name.': '.$value."\r\n");
      }
      $this->server->connection->write("\r\n".$body);

      // Read response
      $response= ['status' => $this->server->connection->readLine(), 'headers' => [], 'body' => ''];
      while ('' !== ($line= $this->server->connection->readLine())) {
        sscanf($line, "%[^:]: %[^\r]", $name, $value);
        $response['headers'][$name]= $value;
      }
      while (!$this->server->connection->eof()) {
        $response['body'].= $this->server->connection->read();
      }
      return $response;
    } finally {
      $this->server->connection->close();
    }
  }

  #[Test]
  public function malformed_protocol() {
    $this->server->connection->connect();
    try {
      $this->server->connection->write("EHLO example.org\r\n\r\n");
      $status= $this->server->connection->readLine();
    } finally {
      $this->server->connection->close();
    }
    Assert::equals("HTTP/1.1 400 Bad Request", $status);
  }

  #[Test, Values(['1.0', '1.1'])]
  public function returns_http_version($version) {
    $r= $this->send('GET', '/status/200', $version);
    Assert::equals("HTTP/$version 200 OK", $r['status']);
  }

  #[Test]
  public function date_header_always_present() {
    $r= $this->send('GET', '/status/200');
    Assert::true(isset($r['headers']['Date']));
  }

  #[Test]
  public function server_header_always_present() {
    $r= $this->send('GET', '/status/200', '1.1', ['Connection' => 'close']);
    Assert::equals('XP', $r['headers']['Server']);
  }

  #[Test, Values([[200, '200 OK'], [404, '404 Not Found'], [420, '420 Enhance your calm']])]
  public function echo_status($code, $expected) {
    $r= $this->send('GET', '/status/'.$code);
    Assert::equals("HTTP/1.0 $expected", $r['status']);
  }

  #[Test, Values([['', '420 Enhance your calm'], ['message=Custom+message', '420 Custom message']])]
  public function custom_status($query, $expected) {
    $r= $this->send('GET', '/status/420?'.$query);
    Assert::equals("HTTP/1.0 $expected", $r['status']);
  }

  #[Test, Values([[404, '404 Not Found'], [500, '500 Internal Server Error']])]
  public function echo_error($code, $expected) {
    $r= $this->send('GET', '/raise/error/'.$code);
    Assert::equals("HTTP/1.0 $expected", $r['status']);
  }

  #[Test]
  public function dispatching_request() {
    $r= $this->send('GET', '/dispatch');
    Assert::equals("HTTP/1.0 420 Dispatched", $r['status']);
  }

  #[Test, Values(['lang.IllegalAccessException', 'Exception'])]
  public function raising_exception_yield_500($class) {
    $r= $this->send('GET', '/raise/exception/'.$class);
    Assert::equals("HTTP/1.0 500 Internal Server Error", $r['status']);
  }

  #[Test]
  public function unrouted_uris_yield_404() {
    $r= $this->send('GET', '/not-routed');
    Assert::equals("HTTP/1.0 404 Not Found", $r['status']);
  }

  #[Test]
  public function content_comes_with_length() {
    $r= $this->send('GET', '/content?data=Test');
    Assert::equals(['4', 'Test'], [$r['headers']['Content-Length'], $r['body']]);
  }

  #[Test]
  public function post_body_read() {
    $headers= ['Content-Type' => self::FORM_URLENCODED, 'Content-Length' => 9];
    $r= $this->send('POST', '/content', '1.0', $headers, 'data=Test');
    Assert::equals(['4', 'Test'], [$r['headers']['Content-Length'], $r['body']]);
  }

  #[Test]
  public function chunked_body_read() {
    $headers= ['Content-Type' => self::FORM_URLENCODED, 'Transfer-Encoding' => 'chunked'];
    $r= $this->send('POST', '/content', '1.1', $headers, "9\r\ndata=Test\r\n0\r\n\r\n");
    Assert::equals(['4', 'Test'], [$r['headers']['Content-Length'], $r['body']]);
  }

  #[Test]
  public function stream_comes_with_length_in_http10() {
    $r= $this->send('GET', '/stream?data=Test', '1.0');
    Assert::equals(['4', 'Test'], [$r['headers']['Content-Length'], $r['body']]);
  }

  #[Test]
  public function stream_comes_with_chunked_te_in_http11() {
    $r= $this->send('GET', '/stream?data=Test', '1.1');
    Assert::equals('chunked', $r['headers']['Transfer-Encoding']);
    Assert::equals("4\r\nTest\r\n0\r\n\r\n", $r['body']);
  }

  #[Test, Values([1024, 4096, 8192])]
  public function with_large_cookie($length) {
    $header= 'cookie='.str_repeat('*', $length);
    $r= $this->send('GET', '/cookie', '1.0', ['Cookie' => $header]);
    Assert::equals((string)strlen($header), $r['body']);
  }

  #[Test, Values(from: 'messages')]
  public function websocket_message($input, $output) {
    try {
      $ws= new WebSocket($this->server->connection, '/ws');
      $ws->connect(['Origin' => 'http://localhost', 'Host' => 'localhost:80']);
      $ws->send($input);
      $result= $ws->receive();
    } finally {
      $ws->close();
    }
    Assert::equals($output, $result);
  }

  #[Test, Expect(class: ProtocolException::class, message: 'Connection closed (#1007): Not valid utf-8')]
  public function invalid_utf8_passed_to_websocket_text_message() {
    try {
      $ws= new WebSocket($this->server->connection, '/ws');
      $ws->connect(['Origin' => 'http://localhost', 'Host' => 'localhost:80']);
      $ws->send("\xfc");
      $ws->receive();
    } finally {
      $ws->close();
    }
  }

  #[After]
  public function shutdown() {
    $this->server->shutdown();
  }
}