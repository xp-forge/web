<?php namespace web\unittest\server;

use io\IOException;
use test\{Assert, Test};
use web\unittest\Channel;
use xp\web\srv\{ForwardRequests, Worker};

class ForwardRequestsTest {

  /** Creates a HTTP message */
  private function message(...$lines): string {
    return implode("\r\n", $lines);
  }

  /** @return void */
  private function forward(Channel $client, Channel $backend) {
    foreach ((new ForwardRequests(new Worker(null, $backend)))->handleData($client) ?? [] as $_) { }
  }

  #[Test]
  public function can_create() {
    new ForwardRequests(new Worker(null, new Channel([])));
  }

  #[Test]
  public function forward_get_request() {
    $request= $this->message(
      'GET / HTTP/1.0',
      '',
      '',
    );
    $response= $this->message(
      'HTTP/1.0 200 OK',
      'Content-Length: 4',
      '',
      'Test'
    );
    $client= new Channel([$request]);
    $backend= new Channel([$response]);
    $this->forward($client, $backend);

    Assert::equals($request, implode('', $backend->out));
    Assert::equals($response, implode('', $client->out));
  }

  #[Test]
  public function forward_get_request_with_chunked_response() {
    $request= $this->message(
      'GET / HTTP/1.0',
      '',
      '',
    );
    $response= $this->message(
      'HTTP/1.0 200 OK',
      'Transfer-Encoding: chunked',
      '',
      "4\r\nid=2\r\n0\r\n\r\n"
    );
    $client= new Channel([$request]);
    $backend= new Channel([$response]);
    $this->forward($client, $backend);

    Assert::equals($request, implode('', $backend->out));
    Assert::equals($response, implode('', $client->out));
  }

  #[Test]
  public function forward_post_request_with_length() {
    $request= $this->message(
      'POST / HTTP/1.0',
      'Content-Length: 4',
      '',
      'Test',
    );
    $response= $this->message(
      'HTTP/1.0 201 Created',
      'Location: /test/1',
      '',
      ''
    );
    $client= new Channel([$request]);
    $backend= new Channel([$response]);
    $this->forward($client, $backend);

    Assert::equals($request, implode('', $backend->out));
    Assert::equals($response, implode('', $client->out));
  }

  #[Test]
  public function forward_post_request_with_chunked_request() {
    $request= $this->message(
      'POST / HTTP/1.0',
      'Transfer-Encoding: chunked',
      '',
      "4\r\nTest\r\n0\r\n\r\n",
    );
    $response= $this->message(
      'HTTP/1.0 201 Created',
      'Location: /test/1',
      '',
      ''
    );
    $client= new Channel([$request]);
    $backend= new Channel([$response]);
    $this->forward($client, $backend);

    Assert::equals($request, implode('', $backend->out));
    Assert::equals($response, implode('', $client->out));
  }

  #[Test]
  public function backend_socket_closed() {
    $request= $this->message(
      'POST / HTTP/1.0',
      'Transfer-Encoding: chunked',
      '',
      "4\r\nTest\r\n0\r\n\r\n",
    );
    $response= $this->message(
      'HTTP/1.0 201 Created',
      'Location: /test/1',
      '',
      ''
    );
    $client= new Channel([$request]);
    $backend= new Channel([$response]);
    $this->forward($client, $backend);

    Assert::false($backend->isConnected());
  }

  #[Test]
  public function backend_socket_closed_on_errors() {
    $request= $this->message(
      'POST / HTTP/1.0',
      'Transfer-Encoding: chunked',
      '',
      "4\r\nTest\r\n0\r\n\r\n",
    );
    $response= $this->message(
      'HTTP/1.0 201 Created',
      'Location: /test/1',
      '',
      ''
    );
    $client= new class([$request]) extends Channel {
      public function write($chunk) {
        throw new IOException('Test');
      }
    };
    $backend= new Channel([$response]);
    try {
      $this->forward($client, $backend);
    } catch (IOException $expected) {
      // ...
    }

    Assert::false($backend->isConnected());
  }
}