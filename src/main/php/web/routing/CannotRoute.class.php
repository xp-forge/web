<?php namespace web\routing;

class CannotRoute extends \web\Error {

  public function __construct($request) {
    parent::__construct(404, 'Cannot route request to '.$request->uri()->getPath());
  }
}