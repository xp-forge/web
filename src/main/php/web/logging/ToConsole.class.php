<?php namespace web\logging;

use util\Objects;
use util\cmd\Console;

class ToConsole extends Sink {

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
      $hint.= ', '.$kind.': '.Objects::stringOf($value);
    }

    Console::writeLinef(
      "  \e[33m[%s %d %.3fkB]\e[0m %d %s %s%s",
      date('Y-m-d H:i:s'),
      getmypid(),
      memory_get_usage() / 1024,
      $response->status(),
      $request->method(),
      $request->uri()->path().($query ? '?'.$query : ''),
      $hint ? " \e[3m[".substr($hint, 2)."]\e[0m" : ''
    );
  }
}