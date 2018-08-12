<?php namespace xp\web\srv;

use lang\Throwable;
use util\cmd\Console;
use web\Environment;
use xp\web\Source;

abstract class Standalone implements Server {
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
   * @param  string[] $args
   */
  public function serve($source, $profile, $webroot, $docroot, $config, $args) {
    $application= (new Source($source, new Environment($profile, $webroot, $docroot, $config, $args)))->application($args);

    $this->server->setProtocol(new HttpProtocol($application, function($request, $response, $message= null) {
      $query= $request->uri()->query();
      Console::writeLinef(
        "  \e[33m[%s %d %.3fkB]\e[0m %d %s %s %s",
        date('Y-m-d H:i:s'),
        getmypid(),
        memory_get_usage() / 1024,
        $response->status(),
        $request->method(),
        $request->uri()->path().($query ? '?'.$query : ''),
        $message
      );
    }));
    $this->server->init();

    Console::writeLine("\e[33m@", nameof($this), '(HTTP @ ', $this->server->socket->toString(), ")\e[0m");
    Console::writeLine("\e[1mServing ", $source, $config, "\e[0m");
    Console::writeLine("\e[36m", str_repeat('═', 72), "\e[0m");
    Console::writeLine();

    Console::writeLine("\e[33;1m>\e[0m Server started: \e[35;4m", $this->url, "\e[0m (", date('r'), ')');
    Console::writeLine('  PID ', getmypid(), '; press Ctrl+C to exit');
    Console::writeLine();

    $this->server->service();
    $this->server->shutdown();
  }
}