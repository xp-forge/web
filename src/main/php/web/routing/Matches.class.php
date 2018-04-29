<?php namespace web\routing;

abstract class Matches {
  public static $ANY;

  static function __static() {
    self::$ANY= newinstance(Match::class, [], [
      'matches' => function($request) { return true; }
    ]);
  }
}