<?php namespace web\unittest\logging;

use io\streams\MemoryOutputStream;
use test\{Assert, Test};
use util\cmd\Console;
use web\io\{TestInput, TestOutput};
use web\logging\ToConsole;
use web\{Error, Request, Response};

class ToConsoleTest {

  /** 
   * Log a message
   *
   * @param  [:var] $hints
   * @return string
   */
  private function log($hints) {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $memory= new MemoryOutputStream();
    $restore= Console::$out->stream();
    Console::$out->redirect($memory);

    try {
      (new ToConsole())->log($req, $res, $hints);
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