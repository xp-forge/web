<?php namespace web\logging;

use util\log\LogCategory;

/**
 * Base class for all log sinks
 *
 * @test  xp://web.unittest.logging.SinkTest
 */
abstract class Sink {

  /**
   * Writes a log entry
   *
   * @param  string $kind
   * @param  util.URI $uri
   * @param  string $status
   * @param  ?web.Error $error Optional error
   * @return void
   */
  public abstract function log($kind, $uri, $status, $error= null);

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