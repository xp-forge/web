<?php namespace xp\web;

use util\cmd\Console;
use web\Environment;

abstract class Standalone {
  private $server, $url;

  public function __construct($server, $url) {
    $this->server= $server;
    $this->url= $url;
  }

  /**
   * Serve requests
   *
   * @param  string $source
   * @param  string $profile
   * @param  io.Path $webroot
   * @param  io.Path $docroot
   * @param  string[] $config
   */
  public function serve($source, $profile, $webroot, $docroot, $config) {
    $application= (new Source($source, new Environment($profile, $webroot, $docroot, $config)))->application();

    $this->server->setProtocol(new HttpProtocol($application, function($request, $response, $message= null) {
      Console::writeLinef(
        "  \e[33m[%s %d %.3fkB]\e[0m %d %s %s %s",
        date('Y-m-d H:i:s'),
        getmypid(),
        memory_get_usage() / 1024,
        $response->status(),
        $request->method(),
        $request->uri()->getPath(),
        $message
      );
    }));
    $this->server->init();

    Console::writeLine("\e[33m@", nameof($this), '(HTTP @ ', $this->server->socket->toString(), ")\e[0m");
    Console::writeLine("\e[1mServing application ", $application);
    Console::writeLine("\e[36m", str_repeat('═', 72), "\e[0m");
    Console::writeLine();

    Console::writeLine("\e[33;1m>\e[0m Server started: \e[35;4m", $this->url, "\e[0m (", date('r'), ')');
    Console::writeLine('  PID ', getmypid(), '; press Ctrl+C to exit');
    Console::writeLine();

    $this->server->service();
    $this->server->shutdown();
  }
}