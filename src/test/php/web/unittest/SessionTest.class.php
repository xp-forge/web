<?php namespace web\unittest;

use test\{Assert, Test, Values};
use web\Session;

class SessionTest {
  const ID= '0815-4711';
  const EXPIRES= 1744535832;

  /** Returns a provider for the cached value */
  private function provider() {
    return function() {
      static $invoked= 0;

      return ++$invoked;
    };
  }

  /** Creates a new in-memory session */
  private function fixture() {
    return new class(self::ID, self::EXPIRES) extends Session {
      private $id, $expires, $values= [];

      public function __construct($id, $expires) { $this->id= $id; $this->expires= $expires; }

      public function id() { return $this->id; }

      public function expires() { return $this->expires; }

      public function register($name, $value) { $this->values[$name]= $value; }

      public function value($name, $default= null) { return $this->values[$name] ?? $default; }

      public function remove($name) { unset($this->values[$name]); }

      public function destroy() { $this->id= null; }
    };
  }

  #[Test]
  public function id() {
    Assert::equals(self::ID, $this->fixture()->id());
  }

  #[Test]
  public function expires() {
    Assert::equals(self::EXPIRES, $this->fixture()->expires());
  }

  #[Test]
  public function register_value() {
    $fixture= $this->fixture();
    $fixture->register('test', 'value');

    Assert::equals('value', $fixture->value('test'));
  }

  #[Test]
  public function remove_value() {
    $fixture= $this->fixture();
    $fixture->register('test', 'value');
    $fixture->remove('test');

    Assert::null($fixture->value('test'));
  }

  #[Test]
  public function destroy() {
    $fixture= $this->fixture();
    $fixture->destroy();

    Assert::null($fixture->id());
  }
}