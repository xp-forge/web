<?php namespace web\unittest;

use unittest\TestCase;
use util\URI;
use web\Environment;
use web\Listeners;
use web\protocol\Connection;

class ListenersTest extends TestCase {

  #[@test]
  public function can_create() {
    newinstance(Listeners::class, [new Environment('test')], [
      'on' => function() { }
    ]);
  }

  #[@test, @values([
  #  ['http://localhost/test', [['/test' => 'Message']]],
  #  ['http://localhost/test/', [['/test' => 'Message']]],
  #  ['http://localhost/test/chat', [['/test/chat' => 'Message']]],
  #  ['http://localhost/testing', []],
  #  ['http://localhost/prod', []],
  #])]
  public function dispatch_to_callable($uri, $expected) {
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