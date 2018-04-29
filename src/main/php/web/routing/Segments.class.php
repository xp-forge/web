<?php namespace web\routing;

/**
 * Matches a path including segments
 *
 * @test  xp://web.unittest.routing.SegmentsTest
 */
class Segments implements Match {
  private $regex;

  /** @param string $pattern */
  public function __construct($pattern) {
    $this->regex= '#^'.preg_replace('/\{([^}]+)\}/', '(?<$1>[^/]+)', $pattern).'/$#';
  }

  /**
   * Returns whether this target matches a given request
   *
   * @param  web.Request $request
   * @return bool
   */
  public function matches($request) {
    return (bool)preg_match($this->regex, rtrim($request->uri()->path(), '/').'/');
  }
}