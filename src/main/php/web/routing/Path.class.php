<?php namespace web\routing;

/**
 * Matches a path prefix
 *
 * @test  xp://web.unittest.routing.PathTest
 */
class Path implements Match {
  private $prefix;

  /** @param string $prefix */
  public function __construct($prefix) {
    $this->prefix= rtrim($prefix, '/').'/';
  }

  /**
   * Returns whether this target matches a given request
   *
   * @param  web.Request $request
   * @return [:string]|bool
   */
  public function matches($request) {
    $length= strlen($this->prefix);
    $path= $request->uri()->path();

    return 0 === strncmp(rtrim($path, '/').'/', $this->prefix, $length)
      ? ['path' => substr($path, $length - 1) ?: '/']
      : null
    ;
  }
}