<?php namespace web\logging;

use util\cmd\Console;

class ToConsole extends Sink {

  /**
   * Writes a log entry
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  ?web.Error $error Optional error
   * @return void
   */
  public function log($request, $response, $error) {
    $query= $request->uri()->query();
    Console::writeLinef(
      "  \e[33m[%s %d %.3fkB]\e[0m %d %s %s %s",
      date('Y-m-d H:i:s'),
      getmypid(),
      memory_get_usage() / 1024,
      $response->status(),
      $request->method(),
      $request->uri()->path().($query ? '?'.$query : ''),
      $message
    );
  }
}