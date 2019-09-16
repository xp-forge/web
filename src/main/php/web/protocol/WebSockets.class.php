<?php namespace web\protocol;

use lang\Throwable;
use peer\ProtocolException;
use peer\server\ServerProtocol;
use util\Bytes;
use web\Request;
use web\Response;

/**
 * WebSockets protocol
 *
 * @see   https://tools.ietf.org/html/rfc6455
 * @test  xp://web.unittest.protocol.WebSocketsTest
 */
class WebSockets extends Protocol {
  const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

  private $listeners, $logging;
  private $connections= [];
  public $server= null;

  /**
   * Creates a new protocol instance
   *
   * @param  web.Listeners $listeners
   * @param  web.Logging $logging
   */
  public function __construct($listeners, $logging) {
    $this->listeners= $listeners;
    $this->logging= $logging;
  }

  /**
   * Handle HTTP request. Performs WebSocket handshake a described in the RFC
   *
   * @param  string $version HTTP version
   * @param  web.Request $request
   * @param  web.Response $response
   * @return bool Whether to keep socket open
   */
  public function next($version, $request, $response) {
    $response->header('Date', gmdate('D, d M Y H:i:s T'));
    $response->header('Host', $request->header('Host'));

    switch ($request->header('Sec-WebSocket-Version')) {
      case '13':
        // Hash websocket key and well-known GUID
        $accept= base64_encode(sha1($request->header('Sec-WebSocket-Key').self::GUID, true));

        $response->answer(101);
        $response->header('Connection', 'Upgrade');
        $response->header('Upgrade', 'websocket');
        $response->header('Sec-WebSocket-Accept', $accept);
        $response->end();

        // Register connection
        $socket= $request->input()->socket;
        $socket->setTimeout(600);
        $id= (int)$socket->getHandle();
        $this->connections[$id]= new Connection($socket, $id, $request->uri(), $request->headers());
        return true;

      default:
        $response->answer(400);
        $response->header('Connection', 'close');
        $response->send('Unsupported websocket version '.$request->header('Sec-WebSocket-Version'), 'text/plain');
        return false;
    }
  }

  /**
   * Handle client connect (only used in standalone mode)
   *
   * @param  peer.Socket $socket
   */
  public function handleConnect($socket) {
    $input= new SocketInput($socket);
    if ($version= $input->version()) {
      $request= new Request($input);
      $response= new Response(new SocketOutput($socket, $version));
      if ($this->next($version, $request, $response)) return;
    }
    $socket->close();
  }

  /**
   * Handle client disconnect
   *
   * @param  peer.Socket $socket
   */
  public function handleDisconnect($socket) {
    unset($this->connections[(int)$socket->getHandle()]);
    $socket->close();
  }

  /**
   * Handle client data
   *
   * @param  peer.Socket $socket
   * @return void
   */
  public function handleData($socket) {
    $conn= $this->connections[(int)$socket->getHandle()];
    foreach ($conn->receive() as $opcode => $payload) {
      try {
        switch ($opcode) {
          case Opcodes::TEXT:
            if (!preg_match('//u', $payload)) {
              $conn->transmit(Opcodes::CLOSE, pack('n', 1007));
              $this->logging->log('TEXT', $conn->uri(), '1007');
              $socket->close();
              break;
            }

            $r= $this->listeners->dispatch($conn, $payload);
            $this->logging->log('TEXT', $conn->uri(), $r ?: 'OK');
            break;

          case Opcodes::BINARY:
            $r= $this->listeners->dispatch($conn, new Bytes($payload));
            $this->logging->log('BINARY', $conn->uri(), $r ?: 'OK');
            break;

          case Opcodes::PING:  // Answer a PING frame with a PONG
            $conn->transmit(Opcodes::PONG, $payload);
            $this->logging->log('PING', $conn->uri(), 'PONG');
            break;

          case Opcodes::PONG:  // Do not answer PONGs
            break;

          case Opcodes::CLOSE: // Close connection
            if ('' === $payload) {
              $conn->transmit(Opcodes::CLOSE, pack('n', 1000));
              $this->logging->log('CLOSE', $conn->uri(), 1000);
            } else {
              $result= unpack('ncode/a*message', $payload);
              if (!preg_match('//u', $result['message'])) {
                $conn->transmit(Opcodes::CLOSE, pack('n', 1007));
                $this->logging->log('CLOSE', $conn->uri(), 1007);
              } else if ($result['code'] > 2999 || in_array($result['code'], [1000, 1001, 1002, 1003, 1007, 1008, 1009, 1010, 1011])) {
                $conn->transmit(Opcodes::CLOSE, $payload);
                $this->logging->log('CLOSE', $conn->uri(), $result['code']);
              } else {
                $conn->transmit(Opcodes::CLOSE, pack('n', 1002));
                $this->logging->log('CLOSE', $conn->uri(), 1002);
              }
            }
            $socket->close();
            break;

          default:             // Something is incorrect with the wire protocol
            $socket->close();
            throw new ProtocolException('Cannot handle opcode');
        }
      } catch (Throwable $t) {
        $this->logging->log(Opcodes::nameOf($opcode), $conn->uri(), 'ERR', $t);
      } catch (\Throwable $t) {  // PHP 7
        $this->logging->log(Opcodes::nameOf($opcode), $conn->uri(), 'ERR', Throwable::wrap($t));
      } catch (\Exception $e) {  // PHP 5
        $this->logging->log(Opcodes::nameOf($opcode), $conn->uri(), 'ERR', Throwable::wrap($e));
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
    unset($this->connections[(int)$socket->getHandle()]);
  }
}