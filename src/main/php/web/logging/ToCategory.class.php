<?php namespace web\logging;

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
   * @param  [:var] $hints Optional hints
   * @return void
   */
  public function log($request, $response, $hints) {
    $query= $request->uri()->query();
    $uri= $request->uri()->path().($query ? '?'.$query : '');

    if ($hints) {
      $this->cat->warn($response->status(), $request->method(), $uri, $hints);
    } else {
      $this->cat->info($response->status(), $request->method(), $uri);
    }
  }
}