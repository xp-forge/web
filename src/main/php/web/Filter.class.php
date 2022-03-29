<?php namespace web;

interface Filter {

  /**
   * Filter request
   *
   * @param  web.Request
   * @param  web.Response
   * @param  web.filters.Invocation
   * @return var
   */
  public function filter($request, $response, $invocation);
}