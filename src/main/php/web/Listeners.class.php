<?php namespace web;

abstract class Listeners {
  protected $environment;
  private $dispatch= null;

  /**
   * Creates a new web application inside a given environment
   *
   * @param  web.Environment $environment
   */
  public function __construct(Environment $environment) {
    $this->environment= $environment;
  }

  /** @return web.Environment */
  public function environment() { return $this->environment; }

  /**
   * Dispatch a message
   *
   * @param  web.protocol.Connection $connection
   * @param  var $message
   * @return var
   */
  public function dispatch($connection, $message) {
    if (null === $this->dispatch) {
      $this->dispatch= [];
      foreach ($this->on() as $path => $handler) {
        $this->dispatch['#^'.$path.'#']= $handler;
      }
    }

    $path= $connection->uri()->path();
    foreach ($this->dispatch as $pattern => $handler) {
      if (preg_match($pattern, $path)) return $handler($connection, $message);
    }
    return null;
  }

  /**
   * Returns dispatching information
   *
   * @return [:callable]
   */
  public abstract function on();
}