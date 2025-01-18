<?php namespace web\unittest\io;

use io\streams\{InputStream, MemoryInputStream};
use test\{Assert, Test, Values};
use web\io\EventSource;

class EventSourceTest {

  /** Returns an input stream from the given lines */
  private function stream(array $lines): InputStream {
    return new MemoryInputStream(implode("\n", $lines));
  }

  /** @return iterable */
  private function inputs() {
    yield [[], []];
    yield [[''], []];
    yield [['data: One'], [[null => 'One']]];
    yield [['', 'data: One'], [[null => 'One']]];
    yield [['data: One', '', 'data: Two'], [[null => 'One'], [null => 'Two']]];
    yield [['event: test', 'data: One'], [['test' => 'One']]];
    yield [['event: test', 'data: One', '', 'data: Two'], [['test' => 'One'], [null => 'Two']]];
  }

  #[Test]
  public function can_create() {
    new EventSource($this->stream([]));
  }

  #[Test, Values(from: 'inputs')]
  public function events($lines, $expected) {
    $events= new EventSource($this->stream($lines));
    $actual= [];
    foreach ($events as $type => $event) {
      $actual[]= [$type => $event];
    }
    Assert::equals($expected, $actual);
  }
}