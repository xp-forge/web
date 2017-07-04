<?php namespace web;

use lang\Throwable;

class InternalServerError extends Error {

  /**
   * Creates a new internal server error
   *
   * @param  php.Throwable|string $cause
   */
  public function __construct($cause) {
    if ($cause instanceof Throwable) {
      parent::__construct(500, $cause->getMessage(), $cause);
    } else {
      parent::__construct(500, $cause);
    }
  }
}