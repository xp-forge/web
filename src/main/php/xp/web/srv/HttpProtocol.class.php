<?php namespace xp\web\srv;

use Throwable;
use lang\ClassLoader;
use web\{Error, InternalServerError, Request, Response, Headers, Status};

/**
 * HTTP protocol implementation
 *
 * @test  web.unittest.server.HttpProtocolTest
 */
class HttpProtocol extends Switchable {
  private $application, $logging;
  public $server= null;
  private $close= false;

  /**
   * Creates a new protocol instance
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
  private function sendError($request, $response, $error) {
    if (!$response->flushed()) {
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
    $this->logging->exchange($request, $response, ['error' => $error]);
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
   * Handle client data
   *
   * @param  peer.Socket $socket
   * @return void
   */
  public function handleData($socket) {
    $input= new Input($socket);
    yield from $input->consume();

    if ($input->kind & Input::REQUEST) {
      gc_enable();
      $version= $input->version();
      $request= new Request($input);
      $response= new Response(new Output($socket, $version));
      $response->header('Date', Headers::date());
      $response->header('Server', 'XP');

      // HTTP/1.1 defaults to keeping connection alive, HTTP/1.0 defaults to closing
      $connection= $request->header('Connection', '');
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
        $result= null;
        if (Input::REQUEST === $input->kind) {
          $handler= $this->application->service($request, $response);
          yield from $handler;
          $result= $handler->getReturn();
        } else if ($input->kind & Input::TIMEOUT) {
          $response->answer(408);
          $response->send('Client timed out sending status line and request headers', 'text/plain');
          $close= true;
        } else if ($input->kind & Input::EXCESSIVE) {
          $response->answer(431);
          $response->send('Client sent excessively long status line or request headers', 'text/plain');
          $close= true;
        }

        $this->logging->exchange($request, $response);
      } catch (CannotWrite $e) {
        $this->logging->exchange($request, $response, ['warn' => $e]);
      } catch (Error $e) {
        $this->sendError($request, $response, $e);
      } catch (Throwable $e) {
        $this->sendError($request, $response, new InternalServerError($e));
      } finally {
        $response->end();
        $close ? $socket->close() : $request->consume();

        gc_collect_cycles();
        gc_disable();
        clearstatcache();
        \xp::gc();
      }
      return $result;
    }

    // Handle request errors and close the socket
    if (!($input->kind & Input::CLOSE)) {
      $status= '400 Bad Request';
      $error= 'Client sent incomplete HTTP request: "'.addcslashes($input->buffer, "\0..\37!\177..\377").'"';
      $socket->write(sprintf(
        "HTTP/1.1 %s\r\nContent-Type: text/plain\r\nContent-Length: %d\r\nConnection: close\r\n\r\n%s",
        $status,
        strlen($error),
        $error
      ));
    }
    $socket->close();
  }
}