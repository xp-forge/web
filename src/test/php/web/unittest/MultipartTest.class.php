<?php namespace web\unittest;

use unittest\{Assert, Test, Values};
use web\Multipart;
use web\io\Param;

class MultipartTest {
  private $param;

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

  #[Before]
  public function param() {
    $this->param= new Param('key', ['value']);
  }

  #[Test, Values('parts')]
  public function can_create($parts) {
    $params= [];
    new Multipart($parts, $params);
  }

  #[Test, Values('parts')]
  public function peek($parts) {
    $params= [];
    Assert::equals([$this->param], (new Multipart($parts, $params))->peek());
  }

  #[Test, Values('parts')]
  public function all_parts($parts) {
    $params= [];
    Assert::equals([$this->param], iterator_to_array((new Multipart($parts, $params))->parts()));
  }
}