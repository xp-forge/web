<?php namespace web;

interface Filter {

  public function filter($request, $response, $invocation);
}