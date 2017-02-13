<?php namespace web\unittest\handler;

use web\handler\Frontend;
use web\handler\Templates;
use web\handler\Actions;
use web\handler\Action;
use web\Request;
use web\Response;
use peer\URL;

class FrontendTest extends \unittest\TestCase {

  #[@test, @values([
  #  ['/', 'index'],
  #  ['/users', 'users'],
  #  ['/photos/bydate', 'photos/bydate']
  #])]
  public function handle($path, $action) {
    $in= new TestInput(['Test' => 'true']);
    $out= new TestOutput();

    $frontend= new Frontend(
      newinstance(Actions::class, [], [
        'for' => function($request) {
          $name= trim($request->uri()->getPath(), '/') ?: 'index';
          $request->pass('user', 'test');

          return newinstance(Action::class, [], [
            'name'    => function() use($name) { return $name; },
            'perform' => function($request, $response) {
              return ['user' => $request->value('user')];
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
    $frontend->handle(new Request('GET', 'http://localhost'.$path, $in), new Response($out));

    $this->assertEquals(
      [$action => [
        'result'  => ['user' => 'test'],
        'request' => [
          'action'  => $action,
          'headers' => ['Test' => 'true'],
          'params'  => [],
          'uri'     => new URL('http://localhost'.$path)
        ]
      ]],
      $rendered
    );
  }
}