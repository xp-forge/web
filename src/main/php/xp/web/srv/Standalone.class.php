<?php namespace xp\web\srv;

use peer\ServerSocket;
use util\cmd\Console;
use web\Environment;
use xp\web\Source;

class Standalone extends Server {
  private $impl;

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
    $application->initialize();
    $application->routing();

    $socket= new ServerSocket($this->host, $this->port);
    $this->impl->listen($socket, Protocol::multiplex()
      ->serving('http', new HttpProtocol($application, $environment->logging()))
      ->serving('websocket', new WebSocketProtocol(null, $environment->logging()))
    );
    $this->impl->init();

    Console::writeLine("\e[33m@", nameof($this), '(HTTP @ ', $socket->toString(), ")\e[0m");
    Console::writeLine("\e[1mServing {$profile}:", $application, $config, "\e[0m > ", $environment->logging()->target());
    Console::writeLine("\e[36m", str_repeat('═', 72), "\e[0m");

    Console::writeLinef(
      "\e[33;1m>\e[0m Server started: \e[35;4mhttp://%s:%d\e[0m in %.3f seconds\n".
      "  %s - PID %d; press Ctrl+C to exit\n",
      '0.0.0.0' === $this->host ? 'localhost' : $this->host,
      $this->port,
      microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
      date('r'),
      getmypid()
    );

    $this->impl->service();
  }
}