<?php namespace xp\web;

use web\Request;
use web\Response;
use web\Error;
use web\Status;
use lang\Throwable;
use lang\ClassLoader;

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
   * Sends an error
   *
   * @param  web.Response $response
   * @param  int $status
   * @param  lang.Throwable $error
   * @return void
   */
  private function sendError($response, $status, $error) {
    $loader= ClassLoader::getDefault();
    $message= Status::message($status);

    $response->answer($status, $message);
    foreach (['web/error-'.$this->application->environment()->profile().'.html', 'web/error.html'] as $variant) {
      if (!$loader->providesResource($variant)) continue;
      return $response->write(sprintf(
        $loader->getResource($variant),
        $status,
        $message,
        $error->getMessage(),
        $error->toString()
      ));
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

    $input= new Input($socket);
    if (null === ($message= $socket->readLine())) return;
    sscanf($message, '%s %s HTTP/%d.%d', $method, $uri, $major, $minor);

    $request= new Request($method, 'http://localhost:8080'.$uri, $input);
    $response= new Response(new Output($socket));

    try {
      $this->application->service($request, $response);
      $this->logging->__invoke($request, $response);
    } catch (Error $e) {
      $this->sendError($response, $e->status(), $e);
      $this->logging->__invoke($request, $response, $e->compoundMessage());
    } catch (\Throwable $e) {   // PHP7
      $t= Throwable::wrap($e);
      $this->sendError($response, 500, $t);
      $this->logging->__invoke($request, $response, $t->compoundMessage());
    } catch (\Exception $e) {   // PHP5
      $t= Throwable::wrap($e);
      $this->sendError($response, 500, $t);
      $this->logging->__invoke($request, $response, $t->compoundMessage());
    } finally {
      gc_collect_cycles();
      gc_disable();
      clearstatcache();
      \xp::gc();

      if ('Keep-Alive' === $request->header('Connection')) {
        $socket->close();
      }
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
