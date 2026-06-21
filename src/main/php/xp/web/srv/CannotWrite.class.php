<?php namespace xp\web\srv;

use io\OperationFailed;

class CannotWrite extends OperationFailed {

  /** @return string */
  public function toString(): string { return $this->message; }
}