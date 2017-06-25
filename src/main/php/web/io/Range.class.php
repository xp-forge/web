<?php namespace web\io;

/* @see xp://web.io.Ranges */
class Range {
  private $start, $end;

  /**
   * Creates a new range
   *
   * @param  int $start
   * @param  int $end
   */
  public function __construct($start, $end) {
    $this->start= $start;
    $this->end= $end;
  }

  /** @return int */
  public function start() { return $this->start; }

  /** @return int */
  public function end() { return $this->end; }

  /** @return int */
  public function length() { return $this->end - $this->start + 1; }
}