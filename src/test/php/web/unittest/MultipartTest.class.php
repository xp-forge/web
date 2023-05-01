<?php namespace web\unittest;

use test\{Assert, Test, Values};
use web\Multipart;
use web\io\Param;

class MultipartTest {
  private $param;

  public function __construct() {
    $this->param= new Param('key', ['value']);
  }

  /** @return iterator */
  private function parts() {
    yield [[$this->param]];
    yield [(function() { yield $this->param; })()];
    yield [new class($this->param) implements \IteratorAggregate {
      private $param;
      public function __construct($param) { $this->param= $param; }
      public function getIterator() { yield $this->param; }
    }];
  }

  #[Test, Values(from: 'parts')]
  public function can_create($parts) {
    $params= [];
    new Multipart($parts, $params);
  }

  #[Test, Values(from: 'parts')]
  public function peek($parts) {
    $params= [];
    Assert::equals([$this->param], (new Multipart($parts, $params))->peek());
  }

  #[Test, Values(from: 'parts')]
  public function all_parts($parts) {
    $params= [];
    Assert::equals([$this->param], iterator_to_array((new Multipart($parts, $params))->parts()));
  }
}