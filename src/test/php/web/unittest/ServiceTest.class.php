<?php namespace web\unittest;

use unittest\TestCase;
use web\Environment;

class ServiceTest extends TestCase {
  private $environment;

  /** @return void */
  public function setUp() {
    $this->environment= new Environment('test', '.', 'doc_root');
  }


  #[@test]
  public function can_create() {
    new TestService($this->environment);
  }

  #[@test]
  public function environment() {
    $this->assertEquals($this->environment, (new TestService($this->environment))->environment());
  }

  #[@test]
  public function string_representation() {
    $this->assertEquals('web.unittest.TestService(doc_root)', (new TestService($this->environment))->toString());
  }

  #[@test]
  public function compare_to_self() {
    $service= new TestService($this->environment);
    $this->assertEquals(0, $service->compareTo($service));
  }

  #[@test]
  public function compare_to_another_instance() {
    $a= new TestService($this->environment);
    $b= new TestService($this->environment);;
    $this->assertEquals(1, $a->compareTo($b));
  }
}