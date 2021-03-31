<?php namespace xp\web\srv;

use lang\Throwable;
use peer\ServerSocket;
use util\cmd\Console;
use web\Environment;
use xp\web\Source;

abstract class Standalone implements Server {
  private $server, $host, $port;

  public function __construct($server, $host, $port) {
    $this->server= $server;
    $this->host= $host;
    $this->port= $port;
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
    $this->server->listen($socket, new HttpProtocol($application, $environment->logging()));
    $this->server->init();

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

    $this->server->service();
    $this->server->shutdown();
  }
}