<?php namespace web\handler;

use util\URI;
use web\Handler;
use web\io\EventSink;
use websocket\Listeners;

/**
 * WebSocket handler used for routing websocket handshake requests
 *
 * @test  web.unittest.handler.WebSocketTest
 * @see   https://www.rfc-editor.org/rfc/rfc6455
 */
class WebSocket implements Handler {
  const GUID= '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

  private $listener;
  private $allowed= [];

  /**
   * Creates a new websocket handler
   *
   * @param function(websocket.protocol.Connection, string|util.Bytes): var|websocket.Listener $listener
   * @param string[] $origins
   */
  public function __construct($listener, array $origins= []) {
    $this->listener= Listeners::cast($listener);
    foreach ($origins as $allowed) {
      $this->allowed[]= '#'.strtr(preg_quote($allowed, '#'), ['\\*' => '.+']).'#i';
    }
  }

  /**
   * Returns canonicalized base URI
   *
   * @param  util.URI $uri
   * @return string
   */
  private function base($uri) {
    static $ports= ['http' => 80, 'https' => 443];

    return $uri->scheme().'://'.$uri->host().':'.($uri->port() ?? $ports[$uri->scheme()] ?? 0);
  }

  /**
   * Verifies request `Origin` header matches the allowed origins. This
   * header cannot be set by client-side JavaScript in browsers!
   *
   * @param   web.Request $request
   * @param   web.Response $response
   * @return  bool
   */
  public function verify($request, $response) {
    if ($origin= $request->header('Origin')) {
      $base= $this->base(new URI($origin));
      foreach ($this->allowed as $pattern) {
        if (preg_match($pattern, $base)) return true;
      }

      // Same-origin policy
      if (0 === strcasecmp($this->base($request->uri()), $base)) return true;
    }

    $response->answer(403);
    $response->send('Origin not allowed', 'text/plain');
    return false;
  }

  /**
   * Handles a request
   *
   * @param   web.Request $request
   * @param   web.Response $response
   * @return  var
   */
  public function handle($request, $response) {
    switch ($version= (int)$request->header('Sec-WebSocket-Version')) {
      case 13: // RFC 6455
        if (!$this->verify($request, $response)) return;

        $key= $request->header('Sec-WebSocket-Key');
        $response->answer(101);
        $response->header('Sec-WebSocket-Accept', base64_encode(sha1($key.self::GUID, true)));
        foreach ($this->listener->protocols ?? [] as $protocol) {
          $response->header('Sec-WebSocket-Protocol', $protocol, true);
        }
        break;

      case 9: // Reserved version, use for WS <-> SSE translation
        if (!$this->verify($request, $response)) return;

        $response->answer(200);
        $response->header('Content-Type', 'text/event-stream');
        $response->header('Transfer-Encoding', 'chunked');
        $response->trace('websocket', $request->header('Sec-WebSocket-Id'));

        $events= new EventSink($request, $response);
        foreach ($events->receive() as $message) {
          $this->listener->message($events, $message);
        }
        return;

      case 0:
        $response->answer(426);
        $response->send('This service requires use of the WebSocket protocol', 'text/plain');
        return;

      default:
        $response->answer(400);
        $response->send('This service does not support WebSocket version '.$version, 'text/plain');
        return;
    }

    yield 'connection' => ['websocket', [
      'path'     => $request->uri()->resource(),
      'headers'  => $request->headers(),
      'listener' => $this->listener,
    ]];
  }
}