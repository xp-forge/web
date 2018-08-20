<?php namespace web\log;

use util\log\LogCategory;

abstract class Sink {

  /**
   * Writes a log entry
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  string $message Additional message
   * @return void
   */
  public abstract function log($request, $response, $message);

  /** @return string */
  public function target() { return nameof($this); }

  /**
   * Factory method from various sources
   *
   * @param  string|util.log.LogCategory|function(web.Request, web.Response, string): void $arg
   * @return ?self
   */
  public static function of($arg) {
    if ('-' === $arg) {
      return new ToConsole();
    } else if ('@' === $arg) {
      return null;
    } else if (is_callable($arg)) {
      return new ToFunction($arg);
    } else if ($arg instanceof LogCategory) {
      return new ToCategory($arg);
    } else {
      return new ToFile($arg);
    }
  }
}