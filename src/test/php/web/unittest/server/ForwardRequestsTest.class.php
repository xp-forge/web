<?php namespace web\unittest\server;

use test\{Assert, Test};
use web\unittest\Channel;
use xp\web\srv\ForwardRequests;

class ForwardRequestsTest {

  /** Creates a HTTP message */
  private function message(...$lines): string {
    return implode("\r\n", $lines);
  }

  /** @return void */
  private function forward(Channel $client, Channel $backend) {
    foreach ((new ForwardRequests($backend))->handleData($client) ?? [] as $_) { }
  }

  #[Test]
  public function can_create() {
    new ForwardRequests(new Channel([]));
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
}