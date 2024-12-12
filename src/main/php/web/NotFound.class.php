<?php namespace web;

class NotFound extends Error {
  public $path;

  /**
   * Creates a "Not Found" error
   *
   * @param  string $path
   * @param  ?lang.Throwable $cause
   */
  public function __construct($path, $cause= null) {
    parent::__construct(404, 'Cannot find '.$path, $cause);
    $this->path= $path;
  }
}