<?php namespace web\routing;

/**
 * Matches a request method and, optionally a target
 *
 * @test  xp://web.unittest.routing.MatchingTest
 */
class Target implements Match {

  public function __construct($methods, $target) {
    $this->methods= array_flip((array)$methods);
    if ('*' === $target) {
      $this->target= null;
    } else if ($target instanceof Match) {
      $this->target= $target;
    } else {
      $this->target= new InPath($target);
    }
  }

  public function matches($request) {
    return (
      isset($this->methods[$request->method()]) &&
      ($this->target ? $this->target->matches($request) : true)
    );
  }
}