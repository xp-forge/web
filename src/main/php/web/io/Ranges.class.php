<?php namespace web\io;

/**
 * Range Requests
 *
 * @see   https://tools.ietf.org/html/rfc7233
 */
class Ranges {
  private $unit, $sets, $complete;

  /**
   * Creates a ranges instance
   *
   * @param  string $unit
   * @param  web.io.Range[] $sets
   * @param  int $complete
   */
  public function __construct($unit, $sets, $complete) {
    $this->unit= $unit;
    $this->sets= $sets;
    $this->complete= $complete;
  }

  /**
   * Returns a Ranges instance parsed from a given input
   *
   * @param  string $input
   * @param  int $complete
   * @return self or NULL if input is NULL.
   */
  public static function in($input, $complete) {
    if (null === $input) return null;

    sscanf($input, '%[^=]=%[0-9-,]', $unit, $set);

    $sets= [];
    foreach (explode(',', $set) as $range) {
      $end= $complete - 1;
      sscanf($range, '%d-%d', $start, $end);
      $start < 0 && $start+= $complete;
      $sets[]= new Range($start, $end);
    }

    return new self($unit, $sets, $complete);
  }

  /** @return string */
  public function unit() { return $this->unit; }

  /** @return web.io.Range[] */
  public function sets() { return $this->sets; }

  /** @return int */
  public function complete() { return $this->complete; }

  /** @return web.io.Range */
  public function single() { return 1 === sizeof($this->sets) ? $this->sets[0] : null; }

  /** @return bool */
  public function satisfiable() {
    $limit= $this->complete - 1;
    foreach ($this->sets as $r) {
      if ($r->start() > $limit || $r->end() > $limit || $r->end() < $r->start()) return false;
    }
    return true;
  }

  /**
   * Formats a given range
   *
   * @param  web.io.Range $range
   * @return string
   */
  public function format($range) {
    return sprintf('%s %d-%d/%d', $this->unit, $range->start(), $range->end(), $this->complete);
  }
}