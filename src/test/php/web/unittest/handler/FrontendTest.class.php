<?php namespace web\unittest\handler;

use web\handler\Frontend;
use web\handler\Templates;
use web\handler\Actions;
use web\handler\Action;
use web\Request;
use web\Response;
use web\unittest\TestInput;
use web\unittest\TestOutput;
use util\URI;

class FrontendTest extends \unittest\TestCase {

  #[@test, @values([
  #  ['/', 'index'],
  #  ['/users', 'users'],
  #  ['/photos/bydate', 'photos/bydate']
  #])]
  public function handle($path, $action) {
    $in= new TestInput('GET', $path, ['Test' => 'true']);
    $out= new TestOutput();

    $frontend= new Frontend(
      newinstance(Actions::class, [], [
        'from' => function($request) {
          $name= trim($request->uri()->path(), '/') ?: 'index';
          $request->pass('user', 'test');

          return newinstance(Action::class, [], [
            'name'    => function() use($name) { return $name; },
            'perform' => function($request, $response) {
              $response->entity(['user' => $request->value('user')]);
            }
          ]);
        }
      ]),
      newinstance(Templates::class, [], [
        'render' => function($name, $structure) use(&$rendered) {
          $rendered= [$name => $structure];
          return '<html>...</html>';
        }
      ])
    );

    $rendered= null;
    $frontend->handle(new Request($in), new Response($out));

    $this->assertEquals(
      [$action => [
        'result'  => ['user' => 'test'],
        'request' => [
          'action'  => $action,
          'headers' => ['Test' => 'true'],
          'params'  => [],
          'uri'     => new URI('http://localhost', $path)
        ]
      ]],
      $rendered
    );
  }
}