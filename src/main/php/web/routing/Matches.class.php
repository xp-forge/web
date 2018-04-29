<?php namespace web\routing;

abstract class Matches {
  public static $ANY;

  static function __static() {
    self::$ANY= newinstance(Match::class, [], '{
      public function matches($request) { return true; }
    }');
  }
}