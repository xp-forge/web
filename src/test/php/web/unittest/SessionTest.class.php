<?php namespace web\unittest;

use test\{Assert, Test, Values};
use web\Session;

class SessionTest {
  const ID= '0815-4711';

  /** Returns a provider for the cached value */
  private function provider() {
    return function() {
      static $invoked= 0;

      return ++$invoked;
    };
  }

  /** Creates a new in-memory session */
  private function fixture() {
    return new class(self::ID) extends Session {
      private $id, $values= [];

      public function __construct($id) { $this->id= $id; }

      public function id() { return $this->id; }

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

  #[Test, Values([null, 3600])]
  public function cache($ttl) {
    $provider= $this->provider();
    $fixture= $this->fixture();

    $a= $fixture->cache('test', $provider, $ttl);
    $b= $fixture->cache('test', $provider, $ttl);

    Assert::equals([1, 1], [$a, $b]);
  }

  #[Test]
  public function cache_expiry() {
    $provider= $this->provider();
    $fixture= $this->fixture();

    $time= time();
    $a= $fixture->cache('test', $provider, 3600, $time);
    $b= $fixture->cache('test', $provider, 3600, $time + 3600);
    $c= $fixture->cache('test', $provider, 3600, $time + 3601);

    Assert::equals([1, 1, 2], [$a, $b, $c]);
  }

  #[Test]
  public function destroy() {
    $fixture= $this->fixture();
    $fixture->destroy();

    Assert::null($fixture->id());
  }
}