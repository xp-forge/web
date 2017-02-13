<?php namespace web;

class Invocation {

  public function proceed($request, $response) {
    $this->target->route($request, $response);
  }
}