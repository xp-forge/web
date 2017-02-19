<?php namespace web\routing;

abstract class Matches {
  public static $ANY;

  static function __static() {
    self::$ANY= newinstance(Match::class, [], [
      'matches' => function($request) { return true; }
    ]);
  }

  public static function cast($match) {
    if ($match instanceof \Closure) {
      return newinstance(Match::class, [], ['matches' => $match]);
    } else {
      return $match;
    }
  }
}