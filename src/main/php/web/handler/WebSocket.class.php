<?php namespace web\handler;

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

  /** @param function(websocket.protocol.Connection, string|util.Bytes): var|websocket.Listener $listener */
  public function __construct($listener) {
    $this->listener= Listeners::cast($listener);
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
        $key= $request->header('Sec-WebSocket-Key');
        $response->answer(101);
        $response->header('Sec-WebSocket-Accept', base64_encode(sha1($key.self::GUID, true)));
        foreach ($this->listener->protocols ?? [] as $protocol) {
          $response->header('Sec-WebSocket-Protocol', $protocol, true);
        }
        break;

      case 9: // Reserved version, use for WS <-> SSE translation
        $response->answer(200);
        $response->header('Content-Type', 'text/event-stream');
        $response->header('Transfer-Encoding', 'chunked');
        $response->trace('websocket', $request->header('Sec-WebSocket-Id'));

        $events= new EventSink($request, $response);
        try {
          foreach ($events->receive() as $message) {
            $this->listener->message($events, $message);
          }
        } finally {
          $events->flush();
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