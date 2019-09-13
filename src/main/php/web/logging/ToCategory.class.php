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
   * @param  string $kind
   * @param  util.URI $uri
   * @param  string $status
   * @param  ?web.Error $error Optional error
   * @return void
   */
  public function log($kind, $uri, $status, $error= null) {
    $uri= $uri->path().(($query= $uri->query()) ? '?'.$query : '');
    if ($error) {
      $this->cat->warn($status, $kind, $uri, $error);
    } else {
      $this->cat->info($status, $kind, $uri);
    }
  }
}