<?php namespace web;

class CannotRoute extends Error {

  public function __construct($request) {
    parent::__construct(404, 'Cannot route request to '.$request->uri()->path());
  }
}