<?php namespace web\logging;

use io\File;
use lang\IllegalArgumentException;
use util\Objects;

/**
 * Logfile sink writing to a file
 *
 * @test  xp://web.unittest.logging.ToFileTest
 */
class ToFile extends Sink {
  private $file;

  /** @param string|io.File $file */
  public function __construct($file) {
    $this->file= $file instanceof File ? $file->getURI() : $file;
    if (false === file_put_contents($this->file, '', FILE_APPEND | LOCK_EX)) {
      $e= new IllegalArgumentException('Cannot write to '.$this->file);
      \xp::gc(__FILE__);
      throw $e;
    }
  }

  /** @return string */
  public function target() { return nameof($this).'('.$this->file.')'; }

  /**
   * Writes a log entry
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  [:var] $hints Optional hints
   * @return void
   */
  public function log($request, $response, $hints) {
    $query= $request->uri()->query();
    $hint= '';
    foreach ($hints as $kind => $value) {
      $hint.= ', '.$kind.': '.(is_string($value) ? $value : Objects::stringOf($value));
    }

    $line= sprintf(
      "[%s %d %.3fkB] %d %s %s%s\n",
      date('Y-m-d H:i:s'),
      getmypid(),
      memory_get_usage() / 1024,
      $response->status(),
      $request->method(),
      $request->uri()->path().($query ? '?'.$query : ''),
      $hint ? ' ['.substr($hint, 2).']' : ''
    );
    file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
  }
}