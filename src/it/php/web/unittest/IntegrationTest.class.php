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
   * @param  [:string] $headers
   * @param  string $body
   * @return void
   */
  private function send($method, $uri, $headers= [], $body= '') {
    self::$connection->write($method.' '.$uri." HTTP/1.0\r\n");
    foreach ($headers as $name => $value) {
      self::$connection->write($name.': '.$value."\r\n");
    }
    self::$connection->write("\r\n".$body);
  }

  #[@test, @values([
  #  [200, '200 OK'],
  #  [404, '404 Not Found'],
  #  [420, '420 Enhanced your calm'],
  #])]
  public function echo_status($code, $expected) {
    $this->send('GET', '/status/'.$code);

    $status= self::$connection->readLine();
    $this->assertEquals("HTTP/1.0 $expected", $status);
  }

  #[@test]
  public function raising_exception_yield_500() {
    $this->send('GET', '/raise/exception');

    $status= self::$connection->readLine();
    $this->assertEquals("HTTP/1.0 500 Internal Server Error", $status);
  }

  #[@test]
  public function unrouted_uris_yield_404() {
    $this->send('GET', '/not-routed');

    $status= self::$connection->readLine();
    $this->assertEquals("HTTP/1.0 404 Not Found", $status);
  }

  #[@test]
  public function malformed_protocol() {
    self::$connection->write("EHLO example.org\r\n");

    $status= self::$connection->readLine();
    $this->assertEquals("HTTP/1.1 400 Bad Request", $status);
  }
}