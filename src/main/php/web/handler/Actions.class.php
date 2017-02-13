<?php namespace web\handler;

interface Actions {

  /**
   * Returns an action for a given name, which is constructed from
   * the request path.
   *
   * @param  web.Request $request
   * @return web.handler.Action
   */
  public function for($request);
}