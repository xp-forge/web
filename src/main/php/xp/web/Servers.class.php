<?php namespace xp\web;

use lang\{Enum, IllegalArgumentException};
use peer\server\{AsyncServer, PreforkingServer, Server};
use xp\web\srv\{Standalone, Develop};

/** @test web.unittest.server.ServersTest */
abstract class Servers extends Enum {
  public static $ASYNC, $PREFORK, $SEQUENTIAL, $DEVELOP;

  static function __static() {
    self::$ASYNC= new class(0, 'ASYNC') extends Servers {
      static function __static() { }
      public function newInstance($address, $arguments= []) {
        return new Standalone($address, new AsyncServer());
      }
    };
    self::$SEQUENTIAL= new class(1, 'SEQUENTIAL') extends Servers {
      static function __static() { }
      public function newInstance($address, $arguments= []) {
        return new Standalone($address, new Server());
      }
    };
    self::$PREFORK= new class(2, 'PREFORK') extends Servers {
      static function __static() { }
      public function newInstance($address, $arguments= []) {
        return new Standalone($address, new PreforkingServer(null, null, ...$arguments));
      }
    };
    self::$DEVELOP= new class(3, 'DEVELOP') extends Servers {
      static function __static() { }
      public function newInstance($address, $arguments= []) {
        return new Develop($address);
      }
    };
  }

  /**
   * Creates a new instance. Implemented by enum values.
   *
   * @param  string $address
   * @param  var[] $arguments
   * @return xp.web.srv.Server
   */
  public abstract function newInstance($address, $arguments= []);

  /**
   * Gets a server implementation by its name
   *
   * @param  string $name
   * @return self
   * @throws lang.IllegalArgumentException
   */
  public static function named($name) {
    switch (strtolower($name)) {
      case 'async': case 'serve': return self::$ASYNC;
      case 'develop': case 'dev': return self::$DEVELOP;
      case 'prefork': return self::$PREFORK;
      case 'sequential': return self::$SEQUENTIAL;
      default: throw new IllegalArgumentException(sprintf(
        'Unknown server "%s", supported: [%s]',
        $name,
        implode(', ', array_map(function($v) { return strtolower($v->name()); }, self::values()))
      ));
    }
  }
}