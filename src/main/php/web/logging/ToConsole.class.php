<?php namespace web\logging;

use util\cmd\Console;

class ToConsole extends Sink {

  /**
   * Writes a log entry
   *
   * @param  string $kind
   * @param  util.URI $uri
   * @param  string $status
   * @param  ?web.Error $error Optional error
   * @return void
   */
  public function log($kind, $uri, $status, $error= null) {
    Console::writeLinef(
      "  \e[33m[%s %d %.3fkB]\e[0m %s %s %s %s",
      date('Y-m-d H:i:s'),
      getmypid(),
      memory_get_usage() / 1024,
      $status,
      $kind,
      $uri->path().(($query= $uri->query()) ? '?'.$query : ''),
      $error ? $error->toString() : ''
    );
  }
}