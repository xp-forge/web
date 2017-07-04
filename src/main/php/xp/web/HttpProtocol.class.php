<?php namespace xp\web;

use lang\ClassLoader;
use lang\Throwable;
use web\Error;
use web\InternalServerError;
use web\Request;
use web\Response;
use web\Status;

/**
 * HTTP protocol implementation
 */
class HttpProtocol implements \peer\server\ServerProtocol {
  public $server= null;

  /**
   * Creates a new protocol instance
   *
   * @param  web.Application $application
   * @param  function(web.Request, web.Response): void $logging
   */
  public function __construct($application, $logging) {
    $this->application= $application;
    $this->logging= $logging;
  }

  /**
   * Flushes the response
   *
   * @param  web.Response $response
   */
  private function flush($response) {
    if ($response->flushed()) {
      return;
    } else if (null === $response->error) {
      $response->flush();
    } else {
      $loader= ClassLoader::getDefault();
      foreach (['web/error-'.$this->application->environment()->profile().'.html', 'web/error.html'] as $variant) {
        if (!$loader->providesResource($variant)) continue;

        $content= sprintf(
          $loader->getResource($variant),
          $response->error->status(),
          Status::message($response->error->status()),
          $response->error->getMessage(),
          $response->error->toString()
        );
        $response->send($content, 'text/html');
        return;
      }
    }
  }

  /**
   * Initialize Protocol
   *
   * @return bool
   */
  public function initialize() {
    return true;
  }

  /**
   * Handle client connect
   *
   * @param  peer.Socket $socket
   */
  public function handleConnect($socket) {
    // Intentionally empty
  }

  /**
   * Handle client disconnect
   *
   * @param  peer.Socket $socket
   */
  public function handleDisconnect($socket) {
    $socket->close();
  }

  /**
   * Handle client data
   *
   * @param  peer.Socket $socket
   * @return void
   */
  public function handleData($socket) {
    gc_enable();
    try {
      $input= new Input($socket);

      // Ignore malformed requests
      if (null === $input->method()) {
        $socket->close();
        return;
      }

      // Process request
      $request= new Request($input);
      $response= new Response(new Output($socket));

      $this->application->service($request, $response);
      $this->flush($response);
      $this->logging->__invoke($request, $response, $response->error ? $response->error->toString() : null);

      if ('Keep-Alive' === $request->header('Connection')) {
        $request->consume();
      } else {
        $socket->close();
      }
    } catch (Throwable $t) {
      $t->printStackTrace();
      $socket->close();
    } finally {
      gc_collect_cycles();
      gc_disable();
      clearstatcache();
      \xp::gc();
    }
  }

  /**
   * Handle I/O error
   *
   * @param  peer.Socket $socket
   * @param  lang.XPException $e
   */
  public function handleError($socket, $e) {
    // $e->printStackTrace();
    $socket->close();
  }
}
