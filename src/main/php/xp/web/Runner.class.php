<?php namespace xp\web;

use io\Path;
use lang\Throwable;
use util\cmd\Console;
use xp\runtime\Help;

/**
 * Web server
 * ==========
 *
 * - Serve static content from given directory
 *   ```sh
 *   $ xp web -r doc_root -
 *   ```
 * - Run a web application
 *   ```sh
 *   $ xp web com.example.web.Application
 *   ```
 * - On Un*x systems, start multiprocess server with 50 children:
 *   ```sh
 *   $ xp web -m prefork,50 ...
 *   ```
 * - Use [development webserver](http://php.net/features.commandline.webserver):
 *   ```sh
 *   $ xp web -m develop ...
 *   ```
 * The address the server listens to can be supplied via *-a {host}[:{port}]*.
 * The profile can be changed via *-p {profile}* (and can be anything!). One
 * or more configuration sources may be passed via *-c {file.ini|dir}*.
 *
 * The webserver log is sent to standard output by default. It can be redirected
 * to a file via *-l /path/to/logfile.log*.
 */
class Runner {

  /**
   * Entry point
   *
   * @param  string[] $args
   * @return int
   */
  public static function main($args) {
    if (empty($args)) return Help::main([strtr(self::class, '\\', '.')]);

    $webroot= new Path(getcwd());
    $docroot= new Path($webroot, 'static');
    $address= 'localhost:8080';
    $profile= getenv('SERVER_PROFILE');
    $server= Servers::$ASYNC;
    $arguments= [];
    $config= [];
    $source= '.';
    $log= [];

    try {
      for ($i= 0; $i < sizeof($args); $i++) {
         if ('-r' === $args[$i]) {
          $docroot= $args[++$i];
        } else if ('-a' === $args[$i]) {
          $address= $args[++$i];
        } else if ('-p' === $args[$i]) {
          $profile= $args[++$i];
        } else if ('-c' === $args[$i]) {
          $config[]= $args[++$i];
        } else if ('-l' === $args[$i]) {
          $log[]= $args[++$i];
        } else if ('-m' === $args[$i]) {
          $arguments= explode(',', $args[++$i]);
          $server= Servers::named(array_shift($arguments));
        } else if ('-s' === $args[$i]) {
          $source= $args[++$i];
        } else if ('--' === $args[$i]) {
          break;
        } else {
          $source= $args[$i];
          break;
        }
      }

      $server->newInstance($address, $arguments)->serve(
        $source,
        $profile ?: (Servers::$DEVELOP === $server ? 'dev' : 'prod'),
        $webroot,
        $webroot->resolve($docroot),
        $config,
        array_slice($args, $i + 1),
        $log ?: '-'
      );
      return 0;
    } catch (Throwable $t) {
      Console::$err->writeLine('*** Error: ', $t->getMessage());
      return 1;
    }
  }
}