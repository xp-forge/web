<?php namespace web;

class NotFound extends Error {
  public $path;

  public function __construct($path, $cause= null) {
    parent::__construct(404, 'Cannot find '.$path, $cause);
    $this->path= $path;
  }
}