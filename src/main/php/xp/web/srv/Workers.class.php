<?php namespace xp\web\srv;

use io\{Path, IOException};
use lang\archive\ArchiveClassLoader;
use lang\{Runtime, RuntimeOptions, CommandLine, FileSystemClassLoader};
use peer\Socket;

/**
 * Start PHP development webservers as background workers
 *
 * @test  web.unittest.server.WorkersTest
 */
class Workers {
  private $commandLine;

  /**
   * Creates a new worker
   *
   * @param  string|io.Path $docroot
   * @param  lang.IClassLoader[] $classLoaders
   */
  public function __construct($docroot, $classLoaders) {
    $runtime= Runtime::getInstance();

    // Inherit all currently loaded paths acceptable to bootstrapping
    $include= '.'.PATH_SEPARATOR.PATH_SEPARATOR.'.';
    foreach ($classLoaders as $delegate) {
      if ($delegate instanceof FileSystemClassLoader || $delegate instanceof ArchiveClassLoader) {
        $include.= PATH_SEPARATOR.$delegate->path;
      }
    }

    // PHP 7.4 doesn't support ephemeral ports, see this commit which went into PHP 8.0:
    // https://github.com/php/php-src/commit/a61a9fe9a0d63734136f995451a1fd35b0176292
    if (PHP_VERSION_ID <= 80000) {
      $s= stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr, STREAM_SERVER_BIND);
      $listen= stream_socket_get_name($s, false);
      fclose($s);
    } else {
      $listen= '127.0.0.1:0';
    }

    // Replace launching shell with PHP on Un*x
    $os= CommandLine::forName(PHP_OS);
    $this->commandLine= $os->compose($runtime->getExecutable()->getFileName(), array_merge(
      ['-S', $listen, '-t', $docroot],
      $runtime->startupOptions()
        ->withSetting('user_dir', $docroot)
        ->withSetting('include_path', $include)
        ->withSetting('output_buffering', 0)
        ->asArguments()
      ,
      [$runtime->bootstrapScript('web')]
    ));
    if ('WINDOWS' !== $os->name()) $this->commandLine= 'exec '.$this->commandLine;
  }

  /**
   * Launches a worker and returns it.
   * 
   * @throws io.IOException
   * @return xp.web.srv.Worker
   */
  public function launch() {
    if (!($proc= proc_open($this->commandLine, [STDIN, STDOUT, ['pipe', 'w']], $pipes, null, null, ['bypass_shell' => true]))) {
      throw new IOException('Cannot execute `'.$this->commandLine.'`');
    }

    // Parse `[...] PHP 8.3.15 Development Server (http://127.0.0.1:60922) started`
    $lines= [];
    do {
      $line= fgets($pipes[2], 1024);
      $lines[]= $line;
    } while ($line && preg_match('/PHP Warning: /', $line));

    if (!preg_match('/\([a-z]+:\/\/([0-9.]+):([0-9]+)\)/', $line, $matches)) {
      proc_terminate($proc, 2);
      proc_close($proc);
      throw new IOException('Cannot determine bound port: `'.implode('', $lines).'`');
    }

    return new Worker($proc, new Socket($matches[1], (int)$matches[2]));
  }
}