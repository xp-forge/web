<?php namespace web;

use web\handler\Call;

abstract class Handling {

  public static function cast($handler) {
    if ($handler instanceof Handler) {
      return $handler;
    } else {
      return new Call($handler);
    }
  }
}