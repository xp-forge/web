<?php namespace web\unittest;

use lang\{Runtime, IllegalStateException};
use peer\Socket;
use test\Provider;
use test\execution\Context;

class StartServer implements Provider {
  private $server;
  private $process= null;
  public $connection= null;

  /**
   * Constructor
   *
   * @param string $server Server process main class
   */
  public function __construct($server) {
    $this->server= strtr($server, '\\', '.');
  }

  public function values(Context $context): iterable {
    $this->process= Runtime::getInstance()->newInstance(null, 'class', $this->server, []);
    $this->process->in->close();

    // Check if startup succeeded
    $status= $this->process->out->readLine();
    if (2 !== sscanf($status, '+ Service %[0-9.]:%d', $host, $port)) {
      $this->shutdown();
      throw new IllegalStateException('Cannot start server: '.$status, null);
    }

    $this->connection= new Socket($host, $port);
    yield $this;
  }

  /** @return void */
  public function shutdown() {
    if (null === $this->process) return;

    $this->process->err->close();
    $this->process->out->close();
    $this->process->terminate();
    $this->process= null;
  }
}