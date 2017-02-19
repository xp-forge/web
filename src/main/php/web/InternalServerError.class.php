<?php namespace web;

class InternalServerError extends Error {

  /**
   * Creates a new internal server error
   *
   * @param  php.Throwable $cause
   */
  public function __construct($cause) {
    parent::__construct(500, $cause->getMessage(), $cause);
  }
}