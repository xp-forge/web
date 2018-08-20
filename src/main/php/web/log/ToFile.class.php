<?php namespace web\log;

use lang\IllegalArgumentException;

class ToFile extends Sink {
  private $file;

  /** @param string $file */
  public function __construct($file) {
    if (false === file_put_contents($file, '', FILE_APPEND | LOCK_EX)) {
      $e= new IllegalArgumentException('Cannot write to '.$file);
      \xp::gc(__FILE__);
      throw $e;
    }

    $this->file= $file;
  }

  /** @return string */
  public function target() { return nameof($this).'('.$this->file.')'; }

  /**
   * Writes a log entry
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  string $message Additional message
   * @return void
   */
  public function log($request, $response, $message) {
    $query= $request->uri()->query();
    $line= sprintf(
      "[%s %d %.3fkB] %d %s %s %s\n",
      date('Y-m-d H:i:s'),
      getmypid(),
      memory_get_usage() / 1024,
      $response->status(),
      $request->method(),
      $request->uri()->path().($query ? '?'.$query : ''),
      $message
    );
    file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
  }
}