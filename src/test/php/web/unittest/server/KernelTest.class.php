<?php namespace web\unittest\server;

use peer\server\AsynchronousServer;
use test\{Assert, Test};
use xp\web\srv\{Kernel, Switchable};

class KernelTest {

  #[Test]
  public function can_create() {
    new Kernel(new AsynchronousServer());
  }

  #[Test]
  public function protocol() {
    $protocol= new class() extends Switchable {
      public function handleSwitch($socket, $context) { }
      public function handleData($socket) { }
    };

    $kernel= new Kernel(new AsynchronousServer());
    $kernel->serving('http', $protocol);
    
    Assert::equals($protocol, $kernel->protocol('http'));
  }
}