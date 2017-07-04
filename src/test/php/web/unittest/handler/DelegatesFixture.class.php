<?php namespace web\unittest\handler;

use io\streams\Streams;
use io\streams\InputStream;
use lang\IllegalAccessException;
use web\handler\Response;

class DelegatesFixture {

  #[@get('/people/{personId}')]
  public function person($personId) { return 'person:'.$personId; }

  #[@get('/people')]
  public function people($max= 10) { return 'people:max='.$max; }

  #[@get('/')]
  public function index() { return 'index'; }

  #[@options('/')]
  public function options() { return 'options'; }

  #[@put('/people/{personId}/keys/{type}')]
  public function uploadKey($user, $personId, $type, InputStream $stream) {
    return 'person:'.$personId.',type:'.$type.'='.Streams::readAll($stream).' via '.$user;
  }

  #[@put('/people/{personId}/avatar'), @$type: header('Content-Type'), @$bytes: body]
  public function uploadAvatar($user, $personId, $type, $bytes) {
    return 'person:'.$personId.',type:'.$type.'='.$bytes.' via '.$user;
  }

  #[@get('/admin')]
  public function admin() { throw new IllegalAccessException('Cannot access /admin'); }

  #[@get('/login')]
  public function login() { return Response::error(501, 'Login not yet implemented'); }
}