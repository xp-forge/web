<?php namespace web\unittest;

use lang\XPClass;
use test\Assert;
use web\handler\WebSocket;
use web\{Application, Error};

class TestingApplication extends Application {

  /** @return var */
  public function routes() {
    return [
      '/ws' => new WebSocket(function($conn, $payload) {
        $conn->send('Echo: '.$payload);
      }),
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
        $class= XPClass::forName(basename($req->uri()->path()));
        if ($class->isSubclassOf(\Throwable::class)) throw $class->newInstance('Raised');

        // A non-exception class was passed!
        $res->answer(200, 'No error');
        $res->send($class->toString().' is not throwable', 'text/plain');
      },
      '/raise/error' => function($req, $res) {
        $status= basename($req->uri()->path());
        throw new Error($status);
      },
      '/dispatch' => function($req, $res) {
        return $req->dispatch('/status/420', ['message' => 'Dispatched']);
      },
      '/content' => function($req, $res) {
        $res->answer(200);
        $res->send($req->param('data') ?? 'Content', 'text/plain');
      },
      '/cookie' => function($req, $res) {
        $res->answer(200);
        $res->send(strlen($req->header('Cookie')), 'text/plain');
      },
      '/stream' => function($req, $res) {
        $res->answer(200);
        $res->header('Content-Type', 'text/plain');
        $stream= $res->stream();
        $stream->write($req->param('data') ?? 'Streamed');
        $stream->close();
      },
    ];
  }
}