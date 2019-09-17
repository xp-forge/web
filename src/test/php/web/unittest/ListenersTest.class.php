<?php namespace web\unittest;

use peer\server\Server;
use unittest\TestCase;
use util\URI;
use web\Environment;
use web\Listeners;
use web\protocol\Connection;
use web\protocol\Protocol;

class ListenersTest extends TestCase {

  #[@test]
  public function can_create() {
    newinstance(Listeners::class, [new Environment('test')], [
      'on' => function() { /* Implementation irrelevant for this test */ }
    ]);
  }

  #[@test]
  public function serve() {
    $listeners= newinstance(Listeners::class, [new Environment('test')], [
      'on' => function() { /* Implementation irrelevant for this test */ }
    ]);
    $this->assertInstanceOf(Protocol::class, $listeners->serve(new Server()));
  }

  #[@test, @values([
  #  ['http://localhost/test', [['/test' => 'Message']]],
  #  ['http://localhost/test/', [['/test' => 'Message']]],
  #  ['http://localhost/test/chat', [['/test/chat' => 'Message']]],
  #  ['http://localhost/testing', [['/**' => 'Message']]],
  #  ['http://localhost/prod', [['/**' => 'Message']]],
  #])]
  public function dispatch_to_callable($uri, $expected) {
    $invoked= [];
    $listeners= newinstance(Listeners::class, [new Environment('test')], [
      'on' => function() use(&$invoked) {
        return [
          '/test' => function($conn, $message) use(&$invoked) {
            $invoked[]= [rtrim($conn->uri()->path(), '/') => $message];
          },
          '/'     => function($conn, $message) use(&$invoked) {
            $invoked[]= ['/**' => $message];
          }
        ];
      }
    ]);
    $listeners->dispatch(new Connection(new Channel([]), 0, new URI($uri)), 'Message');

    $this->assertEquals($expected, $invoked);
  }

  #[@test, @values([
  #  ['http://localhost/test', [['/test' => 'Message']]],
  #  ['http://localhost/prod', []],
  #])]
  public function dispatch_without_catch_all($uri, $expected) {
    $invoked= [];
    $listeners= newinstance(Listeners::class, [new Environment('test')], [
      'on' => function() use(&$invoked) {
        return [
          '/test' => function($conn, $message) use(&$invoked) {
            $invoked[]= [rtrim($conn->uri()->path(), '/') => $message];
          }
        ];
      }
    ]);
    $listeners->dispatch(new Connection(new Channel([]), 0, new URI($uri)), 'Message');

    $this->assertEquals($expected, $invoked);
  }
}