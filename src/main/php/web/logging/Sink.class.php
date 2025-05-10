<?php namespace web\logging;

use util\log\LogCategory;

/**
 * Base class for all log sinks
 *
 * @test  web.unittest.logging.SinkTest
 */
abstract class Sink {

  /**
   * Writes a log entry
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  [:var] $hints Optional hints
   * @return void
   */
  public abstract function log($request, $response, $hints);

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
    } else if (null === $arg || '' === $arg) {
      return null;
    } else if (is_callable($arg)) {
      return new ToFunction($arg);
    } else if (is_array($arg)) {
      switch (sizeof($arg)) {
        case 0: return null;
        case 1: return self::of($arg[0]);
        default: return new ToAllOf(...$arg);
      }
    } else if ($arg instanceof LogCategory) {
      return new ToCategory($arg);
    } else {
      return new ToFile($arg);
    }
  }
}