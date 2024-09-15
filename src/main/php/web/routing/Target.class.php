<?php namespace web\routing;

/**
 * Matches a request method and, optionally a target
 *
 * @deprecated
 * @test  xp://web.unittest.routing.TargetTest
 */
class Target implements RouteMatch {
  private $methods, $target;

  /**
   * Creates a new target
   *
   * @param  string|string[] $methods HTTP methods, e.g. "GET" or "POST"
   * @param  string|web.routing.Match $target
   */
  public function __construct($methods, $target= '*') {
    $this->methods= array_flip((array)$methods);
    if ('*' === $target) {
      $this->target= null;
    } else if ($target instanceof RouteMatch) {
      $this->target= $target;
    } else {
      $this->target= new Path($target);
    }
  }

  /**
   * Returns whether this target matches a given request
   *
   * @param  web.Request $request
   * @return bool
   */
  public function matches($request) {
    return (
      isset($this->methods[$request->method()]) &&
      ($this->target ? $this->target->matches($request) : true)
    );
  }
}