<?php namespace web\unittest;

use lang\ElementNotFoundException;
use lang\IllegalArgumentException;
use peer\server\Server;
use unittest\TestCase;
use util\URI;
use web\Environment;
use web\Listener;
use web\Listeners;
use web\protocol\Connection;
use web\protocol\Protocol;

class ListenersTest extends TestCase {
  const ID = 42;

  /**
   * Returns a Listeners instance wth a given implementation of `on()`.
   *
   * @param  function(): [:var] $on
   * @return web.Listeners
   */
  private function fixture($on= null) {
    return newinstance(Listeners::class, [new Environment('test')], [
      'on' => $on ?: function() { /* Implementation irrelevant for this test */ }
    ]);
  }

  #[@test]
  public function can_create() {
    $this->fixture();
  }

  #[@test]
  public function serve() {
    $this->assertInstanceOf(Protocol::class, $this->fixture()->serve(new Server()));
  }

  #[@test]
  public function connections_initially_empty() {
    $this->assertEquals([], $this->fixture()->connections());
  }

  #[@test]
  public function attach() {
    $conn= new Connection(new Channel([]), self::ID, new URI('/ws'), []);
    $listeners= $this->fixture();
    $listeners->attach(self::ID, $conn);

    $this->assertEquals([self::ID => $conn], $listeners->connections());
  }

  #[@test]
  public function detach() {
    $listeners= $this->fixture();
    $listeners->attach(self::ID, new Connection(new Channel([]), self::ID, new URI('/ws'), []));
    $listeners->detach(self::ID);

    $this->assertEquals([], $listeners->connections());
  }

  #[@test]
  public function connection() {
    $conn= new Connection(new Channel([]), self::ID, new URI('/ws'), []);
    $listeners= $this->fixture();
    $listeners->attach(self::ID, $conn);

    $this->assertEquals($conn, $listeners->connection(self::ID));
  }

  #[@test, @expect(ElementNotFoundException::class)]
  public function non_existant_connection() {
    $this->fixture()->connection(self::ID);
  }

  #[@test]
  public function cast_function() {
    $function= function($connection, $message) { };
    $this->assertEquals($function, Listeners::cast($function));
  }

  #[@test]
  public function cast_listener() {
    $listener= newinstance(Listener::class, [], [
      'message' => function($connection, $message) { }
    ]);
    $this->assertEquals([$listener, 'message'], Listeners::cast($listener));
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function cast_illegal() {
    Listeners::cast($this);
  }

  #[@test, @values([
  #  ['http://localhost/test', [['/test' => 'Message']]],
  #  ['http://localhost/test/', [['/test' => 'Message']]],
  #  ['http://localhost/test/chat', [['/test/chat' => 'Message']]],
  #  ['http://localhost/testing', [['/**' => 'Message']]],
  #  ['http://localhost/prod', [['/**' => 'Message']]],
  #  ['http://localhost/listen', [['/listen' => 'Message']]],
  #])]
  public function dispatch_to_callable($uri, $expected) {
    $invoked= [];
    $listeners= $this->fixture(function() use(&$invoked) {
      return [
        '/listen' => newinstance(Listener::class, [], [
          'message' => function($conn, $message) use(&$invoked) {
            $invoked[]= [rtrim($conn->uri()->path(), '/') => $message];
          }
        ]),
        '/test'   => function($conn, $message) use(&$invoked) {
          $invoked[]= [rtrim($conn->uri()->path(), '/') => $message];
        },
        '/'       => function($conn, $message) use(&$invoked) {
          $invoked[]= ['/**' => $message];
        }
      ];
    });
    $listeners->dispatch(new Connection(new Channel([]), 0, new URI($uri)), 'Message');

    $this->assertEquals($expected, $invoked);
  }

  #[@test, @values([
  #  ['http://localhost/test', [['/test' => 'Message']]],
  #  ['http://localhost/prod', []],
  #])]
  public function dispatch_without_catch_all($uri, $expected) {
    $invoked= [];
    $listeners= $this->fixture(function() use(&$invoked) {
      return [
        '/test' => function($conn, $message) use(&$invoked) {
          $invoked[]= [rtrim($conn->uri()->path(), '/') => $message];
        }
      ];
    });
    $listeners->dispatch(new Connection(new Channel([]), 0, new URI($uri)), 'Message');

    $this->assertEquals($expected, $invoked);
  }
}