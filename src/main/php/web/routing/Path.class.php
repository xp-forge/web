<?php namespace web\routing;

/**
 * Matches a path suffix
 *
 * @test  xp://web.unittest.routing.TargetTest
 */
class Path implements Match {
  private $suffix;

  /** @param  string $suffix */
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
      rtrim($request->uri()->getPath(), '/').'/',
      $this->suffix,
      strlen($this->suffix)
    );
  }
}