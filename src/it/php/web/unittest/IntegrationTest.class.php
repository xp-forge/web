<?php namespace web\unittest;

use unittest\TestCase;

#[@action(new StartServer('web.unittest.TestingServer', 'connected'))]
class IntegrationTest extends TestCase {
  private static $connection;

  /** @param peer.Socket $client */
  public static function connected($client) {
    self::$connection= $client;
  }

  /**
   * Sends a request
   *
   * @param  string $method
   * @param  string $uri
   * @param  string $version
   * @param  [:string] $headers
   * @param  string $body
   * @return void
   */
  private function send($method, $uri, $version= '1.0', $headers= [], $body= '') {
    self::$connection->write($method.' '.$uri.' HTTP/'.$version."\r\n");
    foreach ($headers as $name => $value) {
      self::$connection->write($name.': '.$value."\r\n");
    }
    self::$connection->write("\r\n".$body);
  }

  /**
   * Receives a response
   *
   * @return [:var]
   */
  private function receive() {
    $response= ['status' => self::$connection->readLine(), 'headers' => [], 'body' => ''];
    while ('' !== ($line= self::$connection->readLine())) {
      sscanf($line, "%[^:]: %[^\r]", $name, $value);
      $response['headers'][$name]= $value;
    }
    while (!self::$connection->eof()) {
      $response['body'].= self::$connection->read();
    }
    return $response;
  }

  #[@test, @values(['1.0', '1.1'])]
  public function returns_http_version($version) {
    $this->send('GET', '/status/200', $version, ['Connection' => 'close']);
    $this->assertEquals("HTTP/$version 200 OK", $this->receive()['status']);
  }

  #[@test]
  public function date_header_always_present() {
    $this->send('GET', '/status/200');
    $this->assertTrue(isset($this->receive()['headers']['Date']));
  }

  #[@test]
  public function host_header_present_if_sent() {
    $this->send('GET', '/status/200', '1.1', ['Host' => 'example.org', 'Connection' => 'close']);
    $this->assertEquals('example.org', $this->receive()['headers']['Host']);
  }

  #[@test, @values([
  #  [200, '200 OK'],
  #  [404, '404 Not Found'],
  #  [420, '420 Enhance your calm'],
  #])]
  public function echo_status($code, $expected) {
    $this->send('GET', '/status/'.$code);
    $this->assertEquals("HTTP/1.0 $expected", $this->receive()['status']);
  }

  #[@test, @values([
  #  ['', '420 Enhance your calm'],
  #  ['message=Custom+message', '420 Custom message'],
  #])]
  public function custom_status($query, $expected) {
    $this->send('GET', '/status/420?'.$query);
    $this->assertEquals("HTTP/1.0 $expected", $this->receive()['status']);
  }

  #[@test, @values([
  #  [404, '404 Not Found'],
  #  [500, '500 Internal Server Error'],
  #])]
  public function echo_error($code, $expected) {
    $this->send('GET', '/raise/error/'.$code);
    $this->assertEquals("HTTP/1.0 $expected", $this->receive()['status']);
  }

  #[@test]
  public function dispatching_request() {
    $this->send('GET', '/dispatch');
    $this->assertEquals("HTTP/1.0 420 Dispatched", $this->receive()['status']);
  }

  #[@test, @values([
  #  'lang.IllegalAccessException',
  #  'Exception',
  #])]
  public function raising_exception_yield_500($class) {
    $this->send('GET', '/raise/exception/'.$class);
    $this->assertEquals("HTTP/1.0 500 Internal Server Error", $this->receive()['status']);
  }

  #[@test]
  public function unrouted_uris_yield_404() {
    $this->send('GET', '/not-routed');
    $this->assertEquals("HTTP/1.0 404 Not Found", $this->receive()['status']);
  }

  #[@test]
  public function malformed_protocol() {
    self::$connection->write("EHLO example.org\r\n");
    $this->assertEquals("HTTP/1.1 400 Bad Request", self::$connection->readLine());
  }

  #[@test]
  public function content_comes_with_length() {
    $this->send('GET', '/content?data=Test');
    $response= $this->receive();

    $this->assertEquals('4', $response['headers']['Content-Length']);
    $this->assertEquals('Test', $response['body']);
  }

  #[@test]
  public function stream_comes_with_length_in_http10() {
    $this->send('GET', '/stream?data=Test', '1.0', ['Connection' => 'close']);
    $response= $this->receive();

    $this->assertEquals('4', $response['headers']['Content-Length']);
    $this->assertEquals('Test', $response['body']);
  }

  #[@test]
  public function stream_comes_with_chunked_te_in_http11() {
    $this->send('GET', '/stream?data=Test', '1.1', ['Connection' => 'close']);
    $response= $this->receive();

    $this->assertEquals('chunked', $response['headers']['Transfer-Encoding']);
    $this->assertEquals("4\r\nTest\r\n0\r\n\r\n", $response['body']);
  }
}