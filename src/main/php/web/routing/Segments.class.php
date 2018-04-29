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
   * @return [:string]|bool
   */
  public function matches($request) {
    if (!preg_match($this->regex, rtrim($request->uri()->path(), '/').'/', $r)) return null;

    $matches= [];
    foreach ($r as $key => $value) {
      is_int($key) || $matches[$key]= $value;
    }
    return $matches;
  }
}