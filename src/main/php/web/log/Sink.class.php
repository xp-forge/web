<?php namespace web\log;

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
   * Factory method for command line arguments
   *
   * @param  string $arg
   * @return ?self
   */
  public static function of($arg) {
    if ('-' === $arg) {
      return new ToConsole();
    } else if ('@' === $arg) {
      return null;
    } else {
      return new ToFile($arg);
    }
  }
}