<?php namespace web\routing;

abstract class Matches {
  public static $ANY;

  static function __static() {
    self::$ANY= new class() extends Matches {
      public function matches($request) { return true; }
    };
  }
}