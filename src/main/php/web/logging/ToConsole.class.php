<?php namespace web\logging;

use util\Objects;
use util\cmd\Console;

class ToConsole extends Sink {

  /**
   * Writes a log entry
   *
   * @param  string $status
   * @param  string $method
   * @param  string $resource
   * @param  [:var] $hints Optional hints
   * @return void
   */
  public function log($status, $method, $resource, $hints) {
    $hint= '';
    foreach ($hints as $kind => $value) {
      $hint.= ', '.$kind.': '.(is_string($value) ? $value : Objects::stringOf($value));
    }

    Console::writeLinef(
      "  \e[33m[%s %d %.3fkB]\e[0m %s %s %s%s",
      date('Y-m-d H:i:s'),
      getmypid(),
      memory_get_usage() / 1024,
      $status,
      $method,
      $resource,
      $hint ? " \e[2m[".substr($hint, 2)."]\e[0m" : ''
    );
  }
}