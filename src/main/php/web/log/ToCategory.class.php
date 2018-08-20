<?php namespace web\log;

class ToCategory extends Sink {
  private $cat;

  /** @param util.log.LogCategory $cat */
  public function __construct($cat) {
    $this->cat= $cat;
  }

  /** @return string */
  public function target() { return nameof($this).'('.$this->cat->toString().')'; }

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
    $uri= $request->uri()->path().($query ? '?'.$query : '');

    if ($message) {
      $this->cat->warn($response->status(), $request->method(), $uri, $message);
    } else {
      $this->cat->info($response->status(), $request->method(), $uri);
    }
  }
}