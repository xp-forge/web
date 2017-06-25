<?php namespace web\unittest;

use lang\FormatException;
use lang\IllegalArgumentException;
use web\io\Ranges;
use web\io\Range;

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

  #[@test]
  public function can_create() {
    new Ranges(self::UNIT, $this->newSets(1), self::COMPLETE);
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function sets_may_not_be_empty() {
    new Ranges(self::UNIT, [], self::COMPLETE);
  }

  #[@test, @values(['bytes', 'characters'])]
  public function unit($unit) {
    $this->assertEquals($unit, (new Ranges($unit, $this->newSets(1), self::COMPLETE))->unit());
  }

  #[@test]
  public function complete() {
    $this->assertEquals(self::COMPLETE, (new Ranges(self::UNIT, $this->newSets(1), self::COMPLETE))->complete());
  }

  #[@test, @values([1, 2])]
  public function sets($num) {
    $sets= $this->newSets($num);
    $this->assertEquals($sets, (new Ranges(self::UNIT, $sets, self::COMPLETE))->sets());
  }

  #[@test]
  public function single_set() {
    $sets= $this->newSets(1);
    $this->assertEquals($sets[0], (new Ranges(self::UNIT, $sets, self::COMPLETE))->single());
  }

  #[@test]
  public function multiple_sets() {
    $this->assertNull((new Ranges(self::UNIT, $this->newSets(2), self::COMPLETE))->single());
  }

  #[@test]
  public function no_ranges_in_null() {
    $this->assertNull(Ranges::in(null, self::COMPLETE));
  }

  #[@test, @values([
  #  [[new Range(0, 99)], true],
  #  [[new Range(99, 99)], true],
  #  [[new Range(0, 100)], false],
  #  [[new Range(100, 100)], false],
  #  [[new Range(100, 99)], false],
  #  [[new Range(100, 0)], false]
  #])]
  public function satisfiable($sets, $expected) {
    $this->assertEquals($expected, (new Ranges(self::UNIT, $sets, 100))->satisfiable());
  }

  #[@test]
  public function single_set_in() {
    $this->assertEquals(
      new Ranges('bytes', [new Range(0, self::COMPLETE - 1)], self::COMPLETE),
      Ranges::in('bytes=0-99', self::COMPLETE)
    );
  }

  #[@test]
  public function multiple_sets_in() {
    $last= self::COMPLETE - 1;
    $this->assertEquals(
      new Ranges('bytes', [new Range(0, 0), new Range($last, $last)], self::COMPLETE),
      Ranges::in('bytes=0-0,-1', self::COMPLETE)
    );
  }

  #[@test, @values([1, 10])]
  public function last_n($offset) {
    $this->assertEquals(
      new Ranges('bytes', [new Range(self::COMPLETE - $offset, self::COMPLETE - 1)], self::COMPLETE),
      Ranges::in('bytes=-'.$offset, self::COMPLETE)
    );
  }

  #[@test, @values([0, 1])]
  public function starting_at_until_end($offset) {
    $this->assertEquals(
      new Ranges('bytes', [new Range($offset, self::COMPLETE - 1)], self::COMPLETE),
      Ranges::in('bytes='.$offset.'-', self::COMPLETE)
    );
  }

  #[@test, @expect(FormatException::class), @values([
  #  'bytes',
  #  'bytes=',
  #  'bytes=a-c',
  #  'bytes=a-',
  #  'bytes=-c',
  #  'bytes=0-0,INVALID',
  #])]
  public function invalid_range($input) {
    Ranges::in($input, self::COMPLETE);
  }

  #[@test]
  public function format() {
    $range= new Range(0, 99);
    $this->assertEquals('bytes 0-99/100', (new Ranges('bytes', [$range], 100))->format($range));
  }
}