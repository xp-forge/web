<?php namespace web\unittest;

use lang\{FormatException, IllegalArgumentException};
use unittest\{Expect, Test, Values};
use web\io\{Range, Ranges};

class RangesTest extends \unittest\TestCase {
  const UNIT = 'bytes';
  const COMPLETE = 100;

  /**
   * Creates sets
   *
   * @param  int $num
   * @return web.io.Range[]
   */
  private function newSets($num) {
    $r= [];
    for ($i= 0; $i < $num; $i++) {
      $r[]= new Range($i, $i + self::COMPLETE - 1);
    }
    return $r;
  }

  /** @return iterable */
  private function satisfiable() {
    yield [[new Range(0, 99)], true];
    yield [[new Range(99, 99)], true];
    yield [[new Range(0, 100)], false];
    yield [[new Range(100, 100)], false];
    yield [[new Range(100, 99)], false];
    yield [[new Range(100, 0)], false];
  }

  #[Test]
  public function can_create() {
    new Ranges(self::UNIT, $this->newSets(1), self::COMPLETE);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function sets_may_not_be_empty() {
    new Ranges(self::UNIT, [], self::COMPLETE);
  }

  #[Test, Values(['bytes', 'characters'])]
  public function unit($unit) {
    $this->assertEquals($unit, (new Ranges($unit, $this->newSets(1), self::COMPLETE))->unit());
  }

  #[Test]
  public function complete() {
    $this->assertEquals(self::COMPLETE, (new Ranges(self::UNIT, $this->newSets(1), self::COMPLETE))->complete());
  }

  #[Test, Values([1, 2])]
  public function sets($num) {
    $sets= $this->newSets($num);
    $this->assertEquals($sets, (new Ranges(self::UNIT, $sets, self::COMPLETE))->sets());
  }

  #[Test]
  public function single_set() {
    $sets= $this->newSets(1);
    $this->assertEquals($sets[0], (new Ranges(self::UNIT, $sets, self::COMPLETE))->single());
  }

  #[Test]
  public function multiple_sets() {
    $this->assertNull((new Ranges(self::UNIT, $this->newSets(2), self::COMPLETE))->single());
  }

  #[Test]
  public function no_ranges_in_null() {
    $this->assertNull(Ranges::in(null, self::COMPLETE));
  }

  #[Test, Values('satisfiable')]
  public function are_satisfiable($sets, $expected) {
    $this->assertEquals($expected, (new Ranges(self::UNIT, $sets, 100))->satisfiable());
  }

  #[Test]
  public function single_set_in() {
    $this->assertEquals(
      new Ranges('bytes', [new Range(0, self::COMPLETE - 1)], self::COMPLETE),
      Ranges::in('bytes=0-99', self::COMPLETE)
    );
  }

  #[Test]
  public function multiple_sets_in() {
    $last= self::COMPLETE - 1;
    $this->assertEquals(
      new Ranges('bytes', [new Range(0, 0), new Range($last, $last)], self::COMPLETE),
      Ranges::in('bytes=0-0,-1', self::COMPLETE)
    );
  }

  #[Test, Values([1, 10])]
  public function last_n($offset) {
    $this->assertEquals(
      new Ranges('bytes', [new Range(self::COMPLETE - $offset, self::COMPLETE - 1)], self::COMPLETE),
      Ranges::in('bytes=-'.$offset, self::COMPLETE)
    );
  }

  #[Test, Values([0, 1])]
  public function starting_at_until_end($offset) {
    $this->assertEquals(
      new Ranges('bytes', [new Range($offset, self::COMPLETE - 1)], self::COMPLETE),
      Ranges::in('bytes='.$offset.'-', self::COMPLETE)
    );
  }

  #[Test, Expect(FormatException::class), Values(['bytes', 'bytes=', 'bytes=a-c', 'bytes=a-', 'bytes=-c', 'bytes=0-0,INVALID',])]
  public function invalid_range($input) {
    Ranges::in($input, self::COMPLETE);
  }

  #[Test]
  public function format() {
    $range= new Range(0, 99);
    $this->assertEquals('bytes 0-99/100', (new Ranges('bytes', [$range], 100))->format($range));
  }
}