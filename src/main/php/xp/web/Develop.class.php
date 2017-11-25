<?php namespace xp\web;

use util\cmd\Console;
use lang\Runtime;
use lang\RuntimeOptions;
use lang\CommandLine;
use lang\ClassLoader;
use lang\FileSystemClassLoader;
use lang\archive\ArchiveClassLoader;
use peer\Socket;
use io\IOException;

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

    // PHP doesn't start with a nonexistant document root
    if (!$docroot->exists()) {
      $docroot= getcwd();
    }

    // Inherit all currently loaded paths acceptable to bootstrapping
    $include= '.'.PATH_SEPARATOR.PATH_SEPARATOR.'.';
    foreach (ClassLoader::getLoaders() as $delegate) {
      if ($delegate instanceof FileSystemClassLoader || $delegate instanceof ArchiveClassLoader) {
        $include.= PATH_SEPARATOR.$delegate->path;
      }
    }

    // Start `php -S`, the development webserver
    $runtime= Runtime::getInstance();
    $os= CommandLine::forName(PHP_OS);
    $arguments= ['-S', $this->host.':'.$this->port, '-t', $docroot];
    $cmd= $os->compose($runtime->getExecutable()->getFileName(), array_merge(
      $arguments,
      $runtime->startupOptions()->withSetting('user_dir', $docroot)->withSetting('include_path', $include)->asArguments(),
      [$runtime->bootStrapScript('web')]
    ));

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

    $nul= ['file', 'WINDOWS' === $os->name() ? 'NUL' : '/dev/null', 'w'];
    if (!($proc= proc_open($cmd, [STDIN, STDOUT, $nul], $pipes, null, null, ['bypass_shell' => true]))) {
      throw new IOException('Cannot execute `'.$runtime->getExecutable()->getFileName().'`');
    }

    Console::writeLine("\e[33;1m>\e[0m Server started: \e[35;4mhttp://$this->host:$this->port\e[0m (", date('r'), ')');
    Console::writeLine('  PID ', getmypid(), ' : ', proc_get_status($proc)['pid'], '; press Enter to exit');
    Console::writeLine();

    // Inside `xp -supervise`, connect to signalling socket
    if ($port= getenv('XP_SIGNAL')) {
      $s= new Socket('127.0.0.1', $port);
      $s->connect();
      $s->canRead(null) && $s->read();
      $s->close();
    } else {
      Console::read();
      Console::write('> Shut down ');
    }

    // Wait for shutdown
    proc_terminate($proc, 2);
    do {
      Console::write('.');
      $status= proc_get_status($proc);
    } while ($status['running']);

    proc_close($proc);
    Console::writeLine();
    Console::writeLine("\e[33;1m>\e[0m Server stopped. (", date('r'), ')');
  }
}