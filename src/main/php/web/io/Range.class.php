<?php namespace web\io;

class Range {
  private $start, $end;

  public function __construct($start, $end) {
    $this->start= $start;
    $this->end= $end;
  }

  public function start() { return $this->start; }

  public function end() { return $this->end; }

  public function length() { return $this->end - $this->start + 1; }
}