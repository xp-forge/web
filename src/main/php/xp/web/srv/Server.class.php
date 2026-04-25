<?php namespace xp\web\srv;

use peer\Socket;

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

  /**
   * Inside `xp -supervise`, connect to signalling socket. Unfortunately, there
   * is no way to signal "no timeout", so set a pretty high timeout of one year,
   * then catch and handle it by continuing to check for reads.
   *
   * @param int $signal
   * @param peer.server.ServerImplementation $impl
   */
  protected function connect(int $signal, $impl) {
    if (0 === $signal) return;

    $s= new Socket('127.0.0.1', $signal);
    $s->setTimeout(31536000);
    $s->connect();
    $impl->select($s, function() use($impl) {
      try {
        next: yield 'read' => null;
      } catch (SocketTimeoutException $e) {
        goto next;
      }
      $impl->shutdown();
    });
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