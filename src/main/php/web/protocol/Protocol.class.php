<?php namespace web\protocol;

use peer\server\ServerProtocol;

abstract class Protocol implements ServerProtocol {

  /** @return bool */
  public function initialize() { return true; }

  /**
   * Handle HTTP requests
   *
   * @param  string $version HTTP version
   * @param  web.Request $request
   * @param  web.Response $response
   * @return bool Whether to keep socket open
   */
  public abstract function next($version, $request, $response);
}