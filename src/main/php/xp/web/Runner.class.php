<?php namespace xp\web;

use io\Path;
use lang\IllegalArgumentException;
use lang\XPClass;
use xp\runtime\Help;
use xp\web\srv\Develop;
use xp\web\srv\Prefork;
use xp\web\srv\Serve;

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
  private static $modes= [
    'serve'   => Serve::class,
    'prefork' => Prefork::class,
    'develop' => Develop::class
  ];

  /**
   * Creates a server instance
   *
   * @param  string $mode
   * @param  string $address
   * @param  string[] $arguments
   * @return peer.server.Server
   * @throws lang.IllegalArgumentException
   */
  private static function server($mode, $address, $arguments) {
    if (!isset(self::$modes[$mode])) {
      throw new IllegalArgumentException(sprintf(
        'Unkown server mode "%s", supported: [%s]',
        $mode,
        implode(', ', array_keys(self::$modes))
      ));
    }

    $p= strpos($address, ':', '[' === $address{0} ? strpos($address, ']') : 0);
    if (false === $p) {
      $host= $address;
      $port= 8080;
    } else {
      $host= substr($address, 0, $p);
      $port= (int)substr($address, $p + 1);
    }

    return (new XPClass(self::$modes[$mode]))->newInstance($host, $port, ...$arguments);
  }

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
    $profile= getenv('SERVER_PROFILE') ?: 'dev';
    $mode= 'serve';
    $arguments= [];
    $config= [];
    $source= '.';
    $log= [];

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
        $mode= array_shift($arguments);
      } else if ('-s' === $args[$i]) {
        $source= $args[++$i];
      } else {
        $source= $args[$i];
        break;
      }
    }

    self::server($mode, $address, $arguments)->serve(
      $source,
      $profile,
      $webroot,
      $webroot->resolve($docroot),
      $config,
      array_slice($args, $i + 1),
      $log ?: '-'
    );
    return 0;
  }
}