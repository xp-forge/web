<?php namespace web\routing;

interface Match {

  /**
   * Returns whether this target matches a given request
   *
   * @param  web.Request $request
   * @return bool
   */
  public function matches($request);
}