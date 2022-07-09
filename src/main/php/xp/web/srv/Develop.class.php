<?php namespace xp\web\srv;

use io\IOException;
use lang\archive\ArchiveClassLoader;
use lang\{ClassLoader, CommandLine, FileSystemClassLoader, Runtime, RuntimeOptions};
use peer\Socket;
use util\cmd\Console;
use web\{Application, Environment, Logging};

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
    $arguments= ['-S', ('localhost' === $this->host ? '127.0.0.1' : $this->host).':'.$this->port, '-t', $docroot];
    $cmd= $os->compose($runtime->getExecutable()->getFileName(), array_merge(
      $arguments,
      $runtime->startupOptions()->withSetting('user_dir', $docroot)->withSetting('include_path', $include)->asArguments(),
      [$runtime->bootstrapScript('web')]
    ));

    // Export environment
    putenv('DOCUMENT_ROOT='.$docroot);
    putenv('SERVER_PROFILE='.$profile);
    putenv('WEB_SOURCE='.$source.'+xp.web.dev.Console');
    putenv('WEB_CONFIG='.implode(PATH_SEPARATOR, $config));
    putenv('WEB_ROOT='.$webroot);
    putenv('WEB_ARGS='.implode('|', $args));
    putenv('WEB_LOG='.$logging);

    Console::writeLine("\e[33m@", nameof($this), "(HTTP @ `php ", implode(' ', $arguments), "`)\e[0m");
    Console::writeLine("\e[1mServing {$profile}:", $source, $config, "\e[0m > ", Logging::of($logging)->target());
    Console::writeLine("\e[36m", str_repeat('═', 72), "\e[0m");
    Console::writeLine();

    if ('WINDOWS' === $os->name()) {
      $nul= 'NUL';
    } else {
      $nul= '/dev/null';
      $cmd= 'exec '.$cmd;      // Replace launching shell with PHP
    }
    if (!($proc= proc_open($cmd, [STDIN, STDOUT, ['file', $nul, 'w']], $pipes, null, null, ['bypass_shell' => true]))) {
      throw new IOException('Cannot execute `'.$runtime->getExecutable()->getFileName().'`');
    }

    Console::writeLinef(
      "\e[33;1m>\e[0m Server started: \e[35;4mhttp://%s:%d/\e[0m in %.3f seconds\n".
      "  %s - PID %d & %d; press Enter to exit\n",
      '0.0.0.0' === $this->host ? '127.0.0.1' : $this->host,
      $this->port,
      microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
      date('r'),
      getmypid(),
      proc_get_status($proc)['pid']
    );

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
      usleep(100 * 1000);
    } while ($status['running']);

    proc_close($proc);
    Console::writeLine();
    Console::writeLine("\e[33;1m>\e[0m Server stopped. (", date('r'), ')');
  }
}