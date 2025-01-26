<?php namespace web\unittest\logging;

use io\streams\MemoryOutputStream;
use test\{Assert, Test};
use util\cmd\Console;
use web\Error;
use web\logging\ToConsole;

class ToConsoleTest {

  /** 
   * Log a message
   *
   * @param  [:var] $hints
   * @return string
   */
  private function log($hints) {
    $memory= new MemoryOutputStream();
    $restore= Console::$out->stream();
    Console::$out->redirect($memory);

    try {
      (new ToConsole())->log(200, 'GET', '/', $hints);
      return $memory->bytes();
    } finally {
      Console::$out->redirect($restore);
    }
  }

  #[Test]
  public function can_create() {
    new ToConsole();
  }

  #[Test]
  public function log_without_error() {
    Assert::notEquals(0, strlen($this->log([])));
  }

  #[Test]
  public function log_with_error() {
    Assert::notEquals(0, strlen($this->log(['error' => new Error(404, 'Test')])));
  }
}