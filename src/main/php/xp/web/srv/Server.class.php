<?php namespace xp\web\srv;

abstract class Server {
  protected $host, $port;

  /**
   * Creates a new instance
   *
   * @param  string $address
   */
  public function __construct($address) {
    $p= strpos($address, ':', '[' === $address[0] ? strpos($address, ']') : 0);
    if (false === $p) {
      $this->host= $address;
      $this->port= 8080;
    } else {
      $this->host= substr($address, 0, $p);
      $this->port= (int)substr($address, $p + 1);
    }
  }

  /** @return string */
  public function host() { return $this->host; }

  /** @return int */
  public function port() { return $this->port; }

  /**
   * Serve requests
   *
   * @param  string $source
   * @param  string $profile
   * @param  io.Path $webroot
   * @param  io.Path $docroot
   * @param  string[] $config
   * @param  string[] $args
   * @param  string[] $logging
   */
  public abstract function serve($source, $profile, $webroot, $docroot, $config, $args, $logging);
}