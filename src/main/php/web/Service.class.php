<?php namespace web;

use lang\Value;

/**
 * Base class for web applications and websockets listeners
 *
 * @test  xp://web.unittest.ServiceTest
 */
abstract class Service implements Value {
  protected $environment;

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
   * Connect this service to a given server instance
   *
   * @param  peer.server.Server $server
   * @return web.protocol.Protocol
   */
  public abstract function serve($server);

  /** @return string */
  public function toString() { return nameof($this).'('.$this->environment->docroot().')'; }

  /** @return string */
  public function hashCode() { return spl_object_hash($this); }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value === $this ? 0 : 1;
  }
}