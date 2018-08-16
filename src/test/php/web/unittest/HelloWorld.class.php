<?php namespace web\unittest;

use web\Application;

class HelloWorld extends Application {

  /** @return var */
  public function routes() {
    return ['/' => function($req, $res) {
      $res->answer(200);
      $res->send('Hello World', 'text/plain');
    }];
  }
}