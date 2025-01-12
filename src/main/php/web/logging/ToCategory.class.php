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
   * @param  string $status
   * @param  string $method
   * @param  string $uri
   * @param  [:var] $hints Optional hints
   * @return void
   */
  public function log($status, $method, $uri, $hints) {
    if ($hints) {
      $this->cat->warn($status, $method, $uri, $hints);
    } else {
      $this->cat->info($status, $method, $uri);
    }
  }
}