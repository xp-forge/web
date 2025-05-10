<?php namespace web\routing;

/**
 * Matches a path suffix
 *
 * @deprecated
 * @test  web.unittest.routing.PathTest
 */
class Path implements RouteMatch {
  private $suffix;

  /** @param string $suffix */
  public function __construct($suffix) {
    $this->suffix= rtrim($suffix, '/').'/';
  }

  /**
   * Returns whether this target matches a given request
   *
   * @param  web.Request $request
   * @return bool
   */
  public function matches($request) {
    return 0 === strncmp(
      rtrim($request->uri()->path(), '/').'/',
      $this->suffix,
      strlen($this->suffix)
    );
  }
}