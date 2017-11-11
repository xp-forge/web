<?php namespace xp\web;

use util\cmd\Console;
use lang\Runtime;
use lang\RuntimeOptions;

class Develop {
  private $host, $port;

  /**
   * Creates a new instance
   *
   * @param  string $host
   * @param  int $port
   */
  public function __construct($host, $port) {
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
   */
  public function serve($source, $profile, $webroot, $docroot, $config) {
    $runtime= Runtime::getInstance();
    $startup= $runtime->startupOptions();
    $backing= typeof($startup)->getField('backing')->setAccessible(true)->get($startup);

    // PHP doesn't start with a nonexistant document root
    if (!$docroot->exists()) {
      $docroot= getcwd();
    }

    // Start `php -S`, the development webserver
    $arguments= ['-S', $this->host.':'.$this->port, '-t', $docroot];
    $options= newinstance(RuntimeOptions::class, [$backing], [
      'asArguments' => function() use($arguments) {
        return array_merge($arguments, parent::asArguments());
      }
    ]);
    $options->withSetting('user_dir', $docroot);

    // Export environment
    putenv('DOCUMENT_ROOT='.$docroot);
    putenv('SERVER_PROFILE='.$profile);
    putenv('WEB_SOURCE='.$source);
    putenv('WEB_CONFIG='.implode('PATH_SEPARATOR', $config));
    putenv('WEB_ROOT='.$webroot);

    Console::writeLine("\e[33m@", nameof($this), "(HTTP @ `php ", implode(' ', $arguments), "`)\e[0m");
    Console::writeLine("\e[1mServing ", $source, $config, "\e[0m");
    Console::writeLine("\e[36m", str_repeat('═', 72), "\e[0m");
    Console::writeLine();

    with ($runtime->newInstance($options, 'web'), function($proc) {
      $proc->in->close();
      Console::writeLine("\e[33;1m>\e[0m Server started: \e[35;4mhttp://$this->host:$this->port\e[0m (", date('r'), ')');
      Console::writeLine('  PID ', $proc->getProcessId(), '; press Ctrl+C to exit');
      Console::writeLine();

      while (is_string($line= $proc->err->readLine())) {
        Console::writeLine($line);
      }
    });
  }
}