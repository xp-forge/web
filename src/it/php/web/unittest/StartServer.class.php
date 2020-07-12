<?php namespace web\unittest;

use lang\{Runtime, XPClass};
use peer\Socket;
use unittest\{PrerequisitesNotMetError, Test, TestAction, TestClassAction};

class StartServer implements TestAction, TestClassAction {
  private $server, $connected, $process, $client;

  /**
   * Constructor
   *
   * @param string $server Server process main class
   * @param string $connected Name of connection callback
   */
  public function __construct($server, $connected) {
    $this->server= $server;
    $this->connected= $connected;
  }

  /**
   * Starts server
   *
   * @param  lang.XPClass $c
   * @return void
   * @throws unittest.PrerequisitesNotMetError
   */
  public function beforeTestClass(XPClass $c) {
    $this->process= Runtime::getInstance()->newInstance(null, 'class', $this->server, []);
    $this->process->in->close();

    // Check if startup succeeded
    $status= $this->process->out->readLine();
    if (2 !== sscanf($status, '+ Service %[0-9.]:%d', $host, $port)) {
      $this->afterTestClass($c);
      throw new PrerequisitesNotMetError('Cannot start server: '.$status, null);
    }

    $this->client= new Socket($host, $port);
    $c->getMethod($this->connected)->invoke(null, [$this->client]);
  }

  /**
   * This method gets invoked before a test method is invoked, and before
   * the setUp() method is called.
   *
   * @param  unittest.Test $t
   * @return void
   * @throws unittest.PrerequisitesNotMetError
   */
  public function beforeTest(Test $t) {
    $this->client->connect();
  }

  /**
   * This method gets invoked after the test method is invoked and regard-
   * less of its outcome, after the tearDown() call has run.
   *
   * @param  unittest.Test $t
   * @return void
   */
  public function afterTest(Test $t) {
    $this->client->close();
  }

  /**
   * Shuts down server
   *
   * @param  lang.XPClass $c
   * @return void
   */
  public function afterTestClass(XPClass $c) {
    $this->process->err->close();
    $this->process->out->close();
    $this->process->terminate();
  }
}