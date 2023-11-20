<?php namespace web\unittest;

use io\Path;
use lang\{IllegalArgumentException, ClassLoader};
use test\{Assert, Before, Expect, Test};
use web\{Application, Environment};
use xp\web\{ServeDocumentRootStatically, Source};

class SourceTest {
  private $environment;

  #[Before]
  public function environment() {
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
  public function application_class_in_global_namespace() {
    $class= ClassLoader::defineClass('HelloWorld', Application::class, [], [
      'routes' => function() { }
    ]);
    $src= new Source('HelloWorld', $this->environment);
    Assert::instance($class, $src->application());
  }

  #[Test]
  public function application_file() {
    $base= ClassLoader::getDefault()->findClass('web.unittest.HelloWorld')->path;
    $src= new Source(new Path($base, 'web/unittest/HelloWorld.class.php'), $this->environment);
    Assert::instance(HelloWorld::class, $src->application());
  }

  #[Test]
  public function application_class_and_filter() {
    $src= new Source('web.unittest.HelloWorld+xp.web.dev.Console', $this->environment);
    Assert::instance(HelloWorld::class, $src->application());
  }

  #[Test, Expect(class: IllegalArgumentException::class, message: 'Cannot load class not.a.class')]
  public function non_existant_class() {
    (new Source('not.a.class', $this->environment))->application();
  }

  #[Test, Expect(class: IllegalArgumentException::class, message: 'util.Date is not a web.Application')]
  public function unrelated_class() {
    (new Source('util.Date', $this->environment))->application();
  }

  #[Test]
  public function lowercase_xp_namespaced_web_app() {
    $class= ClassLoader::defineClass('xp.lambda.Web', Application::class, [], [
      'routes' => function() { }
    ]);

    $src= new Source('lambda', $this->environment);
    Assert::instance($class, $src->application());
  }
}