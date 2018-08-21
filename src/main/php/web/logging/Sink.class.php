<?php namespace web\logging;

use util\log\LogCategory;

abstract class Sink {

  /**
   * Writes a log entry
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  ?web.Error $error Optional error
   * @return void
   */
  public abstract function log($request, $response, $error);

  /** @return string */
  public function target() { return nameof($this); }

  /**
   * Factory method from various sources
   *
   * @param  var $arg
   * @return ?self
   */
  public static function of($arg) {
    if ('-' === $arg) {
      return new ToConsole();
    } else if (null === $arg) {
      return null;
    } else if (is_callable($arg)) {
      return new ToFunction($arg);
    } else if (is_array($arg)) {
      return new ToAllOf(...$arg);
    } else if ($arg instanceof LogCategory) {
      return new ToCategory($arg);
    } else {
      return new ToFile($arg);
    }
  }
}