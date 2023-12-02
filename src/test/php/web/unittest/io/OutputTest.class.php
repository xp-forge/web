<?php namespace web\unittest\io;

use test\{Assert, Test};
use web\io\Output;

class OutputTest {

  #[Test]
  public function stream_returns_self_if_not_implemented() {
    $out= new class() extends Output {
      public function begin($status, $message, $headers) { }
      public function write($bytes) { }
    };

    Assert::equals($out, $out->stream());
  }

  #[Test]
  public function flush_is_noop_by_default() {
    $out= new class() extends Output {
      public function begin($status, $message, $headers) { }
      public function write($bytes) { }
    };

    Assert::null($out->flush());
  }

  #[Test]
  public function close_calls_finish() {
    $out= new class() extends Output {
      public $finished= 0;
      public function begin($status, $message, $headers) { }
      public function write($bytes) { }
      public function finish() { $this->finished++; }
    };
    $out->close();

    Assert::equals(1, $out->finished);
  }

  #[Test]
  public function finish_only_called_once() {
    $out= new class() extends Output {
      public $finished= 0;
      public function begin($status, $message, $headers) { }
      public function write($bytes) { }
      public function finish() { $this->finished++; }
    };
    $out->close();
    $out->close();

    Assert::equals(1, $out->finished);
  }

  #[Test]
  public function destructor_calls_finish() {
    $finished= 0;
    $out= new class($finished) extends Output {
      private $finished;
      public function __construct(&$finished) { $this->finished= &$finished; }
      public function begin($status, $message, $headers) { }
      public function write($bytes) { }
      public function finish() { $this->finished++; }
    };
    $out= null;

    Assert::equals(1, $finished);
  }
}