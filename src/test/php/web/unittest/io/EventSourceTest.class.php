<?php namespace web\unittest\io;

use io\streams\MemoryInputStream;
use test\{Assert, Test, Values};
use web\io\EventSource;

class EventSourceTest {

  /** @return iterable */
  private function inputs() {
    yield [[], []];
    yield [[''], []];
    yield [['data: One'], [[null => 'One']]];
    yield [['', 'data: One'], [[null => 'One']]];
    yield [['data: One', ''], [[null => 'One']]];
    yield [['data: One', '', 'data: Two'], [[null => 'One'], [null => 'Two']]];
    yield [['event: test', 'data: One'], [['test' => 'One']]];
    yield [['event: test', 'data: One', '', 'data: Two'], [['test' => 'One'], [null => 'Two']]];
    yield [['event: one', 'data: 1', '', 'event: two', 'data: 2'], [['one' => '1'], ['two' => '2']]];
  }

  #[Test]
  public function can_create() {
    new EventSource(new MemoryInputStream(''));
  }

  #[Test, Values(from: 'inputs')]
  public function events($lines, $expected) {
    $events= new EventSource(new MemoryInputStream(implode("\n", $lines)));
    $actual= [];
    foreach ($events as $type => $event) {
      $actual[]= [$type => $event];
    }
    Assert::equals($expected, $actual);
  }
}