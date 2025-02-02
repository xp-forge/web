<?php namespace web\unittest\server;

use test\{Assert, Test};
use xp\web\srv\Workers;

class WorkersTest {

  /** @param function(peer.Socket): void $assertions */
  private function test($assertions) {
    $worker= (new Workers('.', []))->launch();
    try {
      $assertions($worker);
    } finally {
      $worker->shutdown();
    }
  }

  #[Test]
  public function running() {
    $this->test(function($worker) {
      Assert::true($worker->running());
    });
  }

  #[Test]
  public function pid() {
    $this->test(function($worker) {
      Assert::notEquals(null, $worker->pid());
    });
  }

  #[Test]
  public function execute_http_requests() {
    $this->test(function($worker) {
      $worker->socket->connect();
      try {
        $worker->socket->write("GET / HTTP/1.0\r\n\r\n");
        Assert::equals('HTTP/1.0 200 OK', $worker->socket->readLine());
      } finally {
        $worker->socket->close();
      }
    });
  }
}