<?php namespace xp\web\srv;

use lang\ClassLoader;
use peer\ServerSocket;
use peer\server\AsyncServer;
use util\cmd\Console;
use web\{Application, Environment, Logging};
use xp\web\Source;

class Develop extends Server {

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

    // PHP doesn't start with a nonexistant document root
    if (!$docroot->exists()) {
      $docroot= getcwd();
    }
    $workers= new Workers($docroot, ClassLoader::getLoaders());

    // Export environment
    putenv('DOCUMENT_ROOT='.$docroot);
    putenv('SERVER_PROFILE='.$profile);
    putenv('WEB_SOURCE='.$source.'+xp.web.dev.Console');
    putenv('WEB_CONFIG='.implode(PATH_SEPARATOR, $config));
    putenv('WEB_ROOT='.$webroot);
    putenv('WEB_ARGS='.implode('|', $args));
    putenv('WEB_LOG='.$logging);

    Console::writeLine("\e[33m@", nameof($this), "(HTTP @ `php -S [...] -t {$docroot}`)\e[0m");
    Console::writeLine("\e[1mServing {$profile}:", $application, $config, "\e[0m > ", $environment->logging()->target());
    Console::writeLine("\e[36m", str_repeat('═', 72), "\e[0m");

    $backend= $workers->launch();
    Console::writeLinef(
      "\e[33;1m>\e[0m Server started: \e[35;4mhttp://%s:%d/\e[0m in %.3f seconds\n".
      "  %s - PID %d -> %d @ :%d; press Enter to exit\n",
      '0.0.0.0' === $this->host ? '127.0.0.1' : $this->host,
      $this->port,
      microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
      date('r'),
      getmypid(),
      $backend->pid(),
      $backend->socket->port,
    );

    // Start the multiplex protocol in the foreground and forward requests
    $impl= new AsyncServer();
    $impl->listen(new ServerSocket($this->host, $this->port), Protocol::multiplex()
      ->serving('http', new ForwardRequests($backend))
      ->serving('websocket', new WebSocketProtocol(new ForwardMessages($backend)))
    );
    $impl->init();
    $impl->service();
    $impl->shutdown();

    $backend->shutdown();
  }
}