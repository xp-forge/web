<?php namespace web\handler;

interface Action {

  /** @return string */
  public function name();

  /**
   * Performs this action and returns a structure
   *
   * @param   web.Request $request
   * @param   web.Response $response
   * @return  var
   */
  public function perform($request, $response);
}