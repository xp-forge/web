<?php namespace web;

interface Handler {

  /**
   * Handles a request
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @return var
   */
  public function handle($request, $response);
}