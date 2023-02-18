<?php namespace web\unittest;

use io\Path;
use lang\XPClass;
use test\{Assert, Before, Test};
use web\Environment;
use xp\web\{ServeDocumentRootStatically, Source};

class SourceTest {
  private $environment;

  public function __construct() {
    $this->environment= new Environment('dev', '.', 'static', []);
  }

  #[Test]
  public function serve_document_root() {
    $src= new Source('-', $this->environment);
    Assert::instance(ServeDocumentRootStatically::class, $src->application());
  }

  #[Test]
  public function application_class() {
    $src= new Source('web.unittest.HelloWorld', $this->environment);
    Assert::instance(HelloWorld::class, $src->application());
  }

  #[Test]
  public function application_file() {
    $base= XPClass::forName('web.unittest.HelloWorld')->getClassLoader()->path;
    $src= new Source(new Path($base, 'web/unittest/HelloWorld.class.php'), $this->environment);
    Assert::instance(HelloWorld::class, $src->application());
  }

  #[Test]
  public function application_class_and_filter() {
    $src= new Source('web.unittest.HelloWorld+xp.web.dev.Console', $this->environment);
    Assert::instance(HelloWorld::class, $src->application());
  }
}