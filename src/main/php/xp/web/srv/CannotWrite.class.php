<?php namespace xp\web\srv;

use io\IOException;

class CannotWrite extends IOException {

  /** @return string */
  public function toString(): string { return $this->message; }
}