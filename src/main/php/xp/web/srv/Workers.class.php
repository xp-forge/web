<?php namespace xp\web\srv;

use io\{Path, OperationFailed};
use lang\archive\ArchiveClassLoader;
use lang\{Runtime, RuntimeOptions, CommandLine, FileSystemClassLoader};
use peer\Socket;

/**
 * Start PHP development webservers as background workers
 *
 * @test  web.unittest.server.WorkersTest
 */
class Workers {
  private $executable, $arguments;

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

    $this->executable= $runtime->getExecutable()->getFileName();
    $this->arguments= $runtime->startupOptions()
      ->withSetting('user_dir', $docroot)
      ->withSetting('include_path', $include)
      ->withSetting('output_buffering', 0)
      ->asArguments()
    ;
    $this->arguments[]= $runtime->bootstrapScript('web');
  }

  /**
   * Launches a worker and returns it.
   * 
   * @throws io.OperationFailed
   * @return xp.web.srv.Worker
   */
  public function launch() {
    $os= CommandLine::forName(PHP_OS);
    $commandLine= $os->compose($this->executable, ['-S', '127.0.0.1:0', ...$this->arguments]);

    // Replace launching shell with PHP on Un*x
    if ('WINDOWS' !== $os->name()) $commandLine= 'exec '.$commandLine;

    if (!($proc= proc_open($commandLine, [STDIN, STDOUT, ['pipe', 'w']], $pipes, null, null, ['bypass_shell' => true]))) {
      throw new OperationFailed('Cannot execute `'.$commandLine.'`');
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
      throw new OperationFailed('Cannot determine bound port: `'.implode('', $lines).'`');
    }

    return new Worker($proc, new Socket($matches[1], (int)$matches[2]));
  }
}