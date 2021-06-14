<?php namespace web\unittest\logging;

use io\streams\MemoryOutputStream;
use unittest\{Test, TestCase};
use util\cmd\Console;
use web\io\{TestInput, TestOutput};
use web\logging\ToConsole;
use web\{Error, Request, Response};

class ToConsoleTest extends TestCase {

  /** 
   * Log a message
   *
   * @param  ?web.Error $error
   * @return string
   */
  private function log($error) {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $memory= new MemoryOutputStream();
    $restore= Console::$out->stream();
    Console::$out->redirect($memory);

    try {
      (new ToConsole())->log($req, $res, $error);
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
    $this->assertNotEquals(0, strlen($this->log(null)));
  }

  #[Test]
  public function log_with_error() {
    $this->assertNotEquals(0, strlen($this->log(new Error(404, 'Test'))));
  }
}