<?php namespace web\unittest\server;

use test\{Assert, Before, After, Test};
use xp\web\srv\Workers;

class WorkersTest {
  private $worker;

  #[Before]
  public function worker() {
    $this->worker= (new Workers('.', []))->launch();
  }

  #[Test]
  public function running() {
    Assert::true($this->worker->running());
  }

  #[Test]
  public function pid() {
    Assert::notEquals(null, $this->worker->pid());
  }

  #[Test]
  public function execute_http_requests() {
    $this->worker->socket->connect();
    try {
      $this->worker->socket->write("GET / HTTP/1.0\r\n\r\n");
      Assert::matches('/^HTTP\/1.0 [0-9]{3} .+/', $this->worker->socket->readLine());
    } finally {
      $this->worker->socket->close();
    }
  }

  #[After]
  public function shutdown() {
    $this->worker && $this->worker->shutdown();
  }
}