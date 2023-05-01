<?php namespace web\unittest\server;

use lang\IllegalArgumentException;
use test\{Assert, Expect, Test, Values};
use xp\web\Servers;

class ServersTest {

  /** @return iterable */
  private function servers() {
    yield ['async', Servers::$ASYNC];
    yield ['sequential', Servers::$SEQUENTIAL];
    yield ['prefork', Servers::$PREFORK];
    yield ['develop', Servers::$DEVELOP];
  }

  #[Test, Values(from: 'servers')]
  public function named($name, $expected) {
    Assert::equals($expected, Servers::named($name));
  }

  #[Test, Values(['async', 'ASYNC', 'Async'])]
  public function named_is_case_insensitive($variant) {
    Assert::equals(Servers::$ASYNC, Servers::named($variant));
  }

  #[Test]
  public function async_server_is_default() {
    Assert::equals(Servers::$ASYNC, Servers::named('serve'));
  }

  #[Test, Expect(class: IllegalArgumentException::class, message: '/Unknown server "unknown", supported: .+/')]
  public function unknown_server() {
    Servers::named('unknown');
  }

  #[Test]
  public function host() {
    Assert::equals('127.0.0.1', Servers::named('serve')->newInstance('127.0.0.1')->host());
  }

  #[Test]
  public function bind_all() {
    Assert::equals('0.0.0.0', Servers::named('serve')->newInstance('0.0.0.0')->host());
  }

  #[Test]
  public function port_is_8080_by_default() {
    Assert::equals(8080, Servers::named('serve')->newInstance('127.0.0.1')->port());
  }

  #[Test]
  public function use_port_80() {
    Assert::equals(80, Servers::named('serve')->newInstance('127.0.0.1:80')->port());
  }

  #[Test]
  public function supports_ipv6_notation() {
    Assert::equals('[::1]', Servers::named('serve')->newInstance('[::1]')->host());
  }

  #[Test]
  public function supports_ipv6_notation_with_port() {
    Assert::equals(8080, Servers::named('serve')->newInstance('[::1]:8080')->port());
  }
}