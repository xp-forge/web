<?php namespace web\unittest;

use lang\IllegalAccessException;
use web\{Application, Error};

class TestingApplication extends Application {

  /** @return var */
  public function routes() {
    return [
      '/status/420' => function($req, $res) {
        $res->answer(420, $req->param('message') ?? 'Enhance your calm');
        $res->send('Answered with status 420', 'text/plain');
      },
      '/status' => function($req, $res) {
        $status= basename($req->uri()->path());
        $res->answer($status);
        $res->send('Answered with status '.$status, 'text/plain');
      },
      '/raise/exception' => function($req, $res) {
        throw new IllegalAccessException('No access!');
      },
      '/raise/error' => function($req, $res) {
        $status= basename($req->uri()->path());
        throw new Error($status);
      },
      '/dispatch' => function($req, $res) {
        return $req->dispatch('/status/420', ['message' => 'Dispatched']);
      },
    ];
  }
}