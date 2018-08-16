<?php namespace web\unittest;

use io\Path;
use lang\XPClass;
use unittest\TestCase;
use web\Environment;
use xp\web\ServeDocumentRootStatically;
use xp\web\Source;

class SourceTest extends TestCase {
  private $environment;

  /** @return void */
  public function setUp() {
    $this->environment= new Environment('dev', '.', 'static', []);
  }

  #[@test]
  public function serve_document_root() {
    $src= new Source('-', $this->environment);
    $this->assertInstanceOf(ServeDocumentRootStatically::class, $src->application());
  }

  #[@test]
  public function application_class() {
    $src= new Source('web.unittest.HelloWorld', $this->environment);
    $this->assertInstanceOf(HelloWorld::class, $src->application());
  }

  #[@test]
  public function application_file() {
    $base= XPClass::forName('web.unittest.HelloWorld')->getClassLoader()->path;
    $src= new Source(new Path($base, 'web/unittest/HelloWorld.class.php'), $this->environment);
    $this->assertInstanceOf(HelloWorld::class, $src->application());
  }

  #[@test]
  public function application_class_and_filter() {
    $src= new Source('web.unittest.HelloWorld+xp.web.dev.Console', $this->environment);
    $this->assertInstanceOf(HelloWorld::class, $src->application());
  }
}