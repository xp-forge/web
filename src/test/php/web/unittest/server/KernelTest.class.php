<?php namespace web\unittest\server;

use peer\server\AsynchronousServer;
use test\{Assert, Before, Test};
use util\NoSuchElementException;
use xp\web\srv\{Kernel, Switchable};

class KernelTest {
  private $server;

  #[Before]
  public function server() {
    $this->server= new AsynchronousServer();
  }

  #[Test]
  public function can_create() {
    new Kernel($this->server);
  }

  #[Test]
  public function non_existant_protocol() {
    $kernel= new Kernel($this->server);

    Assert::throws(NoSuchElementException::class, fn() => $kernel->protocol('non-existant'));
  }

  #[Test]
  public function protocol() {
    $protocol= new class() extends Switchable {
      public function handleSwitch($socket, $context) { }
      public function handleData($socket) { }
    };

    $kernel= new Kernel($this->server);
    $kernel->serving('http', $protocol);
    
    Assert::equals($protocol, $kernel->protocol('http'));
  }
}