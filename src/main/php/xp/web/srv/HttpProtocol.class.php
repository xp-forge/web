<?php namespace xp\web\srv;

use lang\{ClassLoader, Throwable};
use peer\server\{AsyncServer, ServerProtocol};
use web\{Error, InternalServerError, Request, Response, Headers, Status};

/**
 * HTTP protocol implementation
 *
 * @test  xp://web.unittest.HttpProtocolTest
 */
class HttpProtocol implements ServerProtocol {
  private $application, $logging;
  public $server= null;
  private $close= false;

  /**
   * Creates a new protocol instance
   *
   * @param  web.Application $application
   * @param  web.Logging $logging
   */
  private function __construct($application, $logging) {
    $this->application= $application;
    $this->logging= $logging;
  }

  /**
   * Creates an instance of HTTP protocol executing the given application
   *
   * @param  web.Application $application
   * @param  web.Logging $logging
   * @return self
   */
  public static function executing($application, $logging) {

    // Compatibility with older xp-framework/networking libraries, see issue #79
    // Unwind generators returned from handleData() to guarantee their complete
    // execution.
    if (class_exists(AsyncServer::class, true)) {
      return new self($application, $logging);
    } else {
      return new class($application, $logging) extends HttpProtocol {
        public function handleData($socket) {
          foreach (parent::handleData($socket) as $_) { }
        }
      };
    }
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
    $this->logging->log($request, $response, $error);
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
    $input= new Input($socket);
    if ($version= $input->version()) {
      gc_enable();
      $request= new Request($input);
      $response= new Response(new Output($socket, $version));
      $response->header('Date', Headers::date());
      $response->header('Server', 'XP');

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
        yield from $this->application->service($request, $response) ?? [];
        $this->logging->log($request, $response);
      } catch (Error $e) {
        $this->sendError($request, $response, $e);
      } catch (\Throwable $e) {
        $this->sendError($request, $response, new InternalServerError($e));
      } finally {
        $response->end();
        $close ? $socket->close() : $request->consume();

        gc_collect_cycles();
        gc_disable();
        clearstatcache();
        \xp::gc();
      }
    } else if (Input::CLOSE === $input->kind) {
      $socket->close();
    } else {
      $error= 'Incomplete HTTP request: "'.addcslashes($input->kind, "\0..\37!\177..\377").'"';
      $socket->write(sprintf(
        "HTTP/1.1 400 Bad Request\r\nContent-Type: text/plain\r\nContent-Length: %d\r\nConnection: close\r\n\r\n%s",
        strlen($error),
        $error
      ));
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