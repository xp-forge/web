<?php namespace xp\web\srv;

use peer\ServerSocket;
use util\cmd\Console;
use web\Environment;
use xp\web\Source;

class Standalone extends Server {
  private $impl;

  static function __static() {
    if (defined('SOMAXCONN')) return;

    // Discover SOMAXCONN depending on platform, using 128 as fallback
    // See https://stackoverflow.com/q/1198564
    if (0 === strncasecmp(PHP_OS, 'Win', 3)) {
      $value= 0x7fffffff;
    } else if (file_exists('/proc/sys/net/core/somaxconn')) {
      $value= (int)file_get_contents('/proc/sys/net/core/somaxconn');
    } else if (file_exists('/etc/sysctl.conf')) {
      $value= 128;
      foreach (file('/etc/sysctl.conf') as $line) {
        if (0 === strncmp($line, 'kern.ipc.somaxconn=', 19)) {
          $value= (int)substr($line, 19);
          break;
        }
      }
    } else {
      $value= 128;
    }
    define('SOMAXCONN', $value);
  }

  /**
   * Creates a new instance
   *
   * @param  string $address
   * @param  peer.server.Server $impl
   */
  public function __construct($address, $impl) {
    parent::__construct($address);
    $this->impl= $impl;
  }

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
  public function serve($source, $profile, $webroot, $docroot, $config, $args, $logging) {
    $environment= new Environment($profile, $webroot, $docroot, $config, $args, $logging);
    $application= (new Source($source, $environment))->application($args);
    $application->routing();

    $socket= new ServerSocket($this->host, $this->port);
    $this->impl->listen($socket, HttpProtocol::executing($application, $environment->logging()));
    $this->impl->init();

    Console::writeLine("\e[33m@", nameof($this), '(HTTP @ ', $socket->toString(), ")\e[0m");
    Console::writeLine("\e[1mServing ", $application, $config, "\e[0m > ", $environment->logging()->target());
    Console::writeLine("\e[36m", str_repeat('═', 72), "\e[0m");

    Console::writeLinef(
      "\e[33;1m>\e[0m Server started: \e[35;4mhttp://%s:%d\e[0m in %.3f seconds\n".
      "  %s - PID %d; press Ctrl+C to exit\n",
      '0.0.0.0' === $this->host ? '127.0.0.1' : $this->host,
      $this->port,
      microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
      date('r'),
      getmypid(),
    );

    $this->impl->service();
    $this->impl->shutdown();
  }
}