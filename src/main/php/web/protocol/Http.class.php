<?php namespace web\protocol;

use lang\ClassLoader;
use lang\Throwable;
use web\Error;
use web\InternalServerError;
use web\Request;
use web\Response;
use web\Status;

/**
 * HTTP protocol implementation
 *
 * @test  xp://web.unittest.HttpProtocolTest
 */
class Http extends Protocol {
  private $application, $logging;
  public $server= null;
  private $close= false;

  /**
   * Creates a new HTTP protocol instance
   *
   * @param  web.Application $application
   * @param  web.Logging $logging
   */
  public function __construct($application, $logging) {
    $this->application= $application;
    $this->logging= $logging;
  }

  /**
   * Sends an error
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  web.Error $error
   * @return void
   */
  public function error($request, $response, $error) {
    if ($response->flushed()) {
      $error->printStackTrace();
    } else {
      $loader= ClassLoader::getDefault();
      $message= Status::message($error->status());

      $response->answer($error->status(), $message);
      foreach (['web/error-'.$this->application->environment()->profile().'.html', 'web/error.html'] as $variant) {
        if (!$loader->providesResource($variant)) continue;
        $response->send(sprintf(
          $loader->getResource($variant),
          $error->status(),
          htmlspecialchars($message),
          htmlspecialchars($error->getMessage()),
          htmlspecialchars($error->toString())
        ));
        break;
      }
    }
    $this->logging->log($request->method(), $request->uri(), $error->status(), $error);
  }

  /**
   * Responds to an incomplete HTTP request
   *
   * @param  peer.Socket $socket
   * @param  string $bytes
   * @return void
   */
  public function incomplete($socket, $bytes) {
    $error= 'Incomplete HTTP request: "'.addcslashes($bytes, "\0..\37!\177..\377").'"';
    $socket->write(sprintf(
      "HTTP/1.1 400 Bad Request\r\nContent-Type: text/plain\r\nContent-Length: %d\r\nConnection: close\r\n\r\n%s",
      strlen($error),
      $error
    ));
  }

  /**
   * Handles request for a specific version and returns whether to leave
   * the socket open.
   *
   * @param  string $version
   * @param  web.Request $request
   * @param  web.Response $response
   * @return bool
   */
  public function next($version, $request, $response) {
    $response->header('Date', gmdate('D, d M Y H:i:s T'));
    $response->header('Host', $request->header('Host'));

    // HTTP/1.1 defaults to keeping connection alive, HTTP/1.0 defaults to closing
    $connection= $request->header('Connection');
    if ($this->close) {
      $close= true;
      $response->header('Connection', 'close');
    } else if ($version < '1.1') {
      $close= 0 !== strncasecmp('keep-alive', $connection, 10);
      $close || $response->header('Connection', 'keep-alive');
    } else {
      $close= 0 === strncasecmp('close', $connection, 5);
      $close && $response->header('Connection', 'close');
    }

    try {
      gc_enable();
      $this->application->service($request, $response);
      $this->logging->log($request->method(), $request->uri(), $response->status());
    } catch (Error $e) {
      $this->error($version, $request, $response, $e);
    } catch (\Throwable $e) {   // PHP7
      $this->error($version, $request, $response, new InternalServerError($e));
    } catch (\Exception $e) {   // PHP5
      $this->error($version, $request, $response, new InternalServerError($e));
    } finally {
      $response->end();
      $close || $request->consume();

      gc_collect_cycles();
      gc_disable();
      clearstatcache();
      \xp::gc();
    }
    return !$close;
  }

  /**
   * Initialize Protocol
   *
   * @return bool
   */
  public function initialize() {
    $this->close= (bool)getenv('NO_KEEPALIVE');
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
    $input= new SocketInput($socket);
    if ($version= $input->version()) {
      $this->next($version, new Request($input), new Response(new SocketOutput($socket, $version))) || $socket->close();
    } else if (SocketInput::CLOSE === $input->kind) {
      $socket->close();
    } else {
      $this->incomplete($socket, $input->kind);
      $socket->close();
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
