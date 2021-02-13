<?php namespace web\unittest;

use io\{Path, TempFile};
use lang\ElementNotFoundException;
use unittest\{Expect, Test, TestCase, Values};
use util\{Properties, PropertySource, RegisteredPropertySource};
use web\Environment;

class EnvironmentTest extends TestCase {

  /** @return iterable */
  private function expansions() {
    putenv('XP_TEST=abc');
    yield ['${env.XP_TEST}', function($env) { return $env->variable('XP_TEST'); }];
    yield ['${app.tempDir}/sessions', function($env) { return $env->tempDir().'/sessions'; }];
    yield ['${app.webroot}/sessions', function($env) { return $env->webroot().'/sessions'; }];
    yield ['${app.docroot}/static', function($env) { return $env->docroot().'/static'; }];
    yield ['${app.profile}', function($env) { return $env->profile(); }];
  }

  #[Test]
  public function can_create() {
    new Environment('dev', '.', 'static', []);
  }

  #[Test]
  public function profile() {
    $this->assertEquals('dev', (new Environment('dev', '.', 'static', []))->profile());
  }

  #[Test]
  public function webroot() {
    $this->assertEquals(new Path('.'), (new Environment('dev', '.', 'static', []))->webroot());
  }

  #[Test]
  public function docroot() {
    $this->assertEquals(new Path('static'), (new Environment('dev', '.', 'static', []))->docroot());
  }

  #[Test]
  public function variable() {
    putenv('XP_TEST=abc');
    $this->assertEquals('abc', (new Environment('dev', '.', 'static', []))->variable('XP_TEST'));
  }

  #[Test]
  public function tempDir() {
    $this->assertTrue(is_dir((new Environment('dev', '.', 'static', []))->tempDir()));
  }

  #[Test]
  public function path() {
    $environment= new Environment('dev', '.', 'static', []);
    $this->assertEquals(
      new Path($environment->webroot(), 'src/main/handlebars'),
      $environment->path('src/main/handlebars')
    );
  }

  #[Test]
  public function non_existant_variable() {
    putenv('XP_TEST');
    $this->assertNull((new Environment('dev', '.', 'static', []))->variable('XP_TEST'));
  }

  #[Test, Expect(ElementNotFoundException::class)]
  public function non_existant_properties() {
    (new Environment('dev', '.', 'static', []))->properties('inject');
  }

  #[Test]
  public function properties() {
    $prop= new Properties('inject.ini');
    $environment= new Environment('dev', '.', 'static', [new RegisteredPropertySource('inject', $prop)]);
    $this->assertEquals($prop, $environment->properties('inject'));
  }

  #[Test, Values('expansions')]
  public function property_expansions($value, $expanded) {
    $target= new TempFile();

    // We need to store this to the file system as property expansion
    // is performed when reading from the file system.
    $prop= new Properties();
    $prop->create();
    $prop->writeString(null, 'value', $value);
    $prop->store($target->out());

    $environment= new Environment('dev', '.', 'static', [newinstance(PropertySource::class, [], [
      'provides' => function($name) { return true; },
      'fetch'    => function($name) use($target) { return new Properties($target->getURI()); }
    ])]);

    $this->assertEquals($expanded($environment), $environment->properties('config')->readString(null, 'value'));
  }

  #[Test, Values([[[]], [['test', 'value']]])]
  public function arguments($arguments) {
    $this->assertEquals($arguments, (new Environment('dev', '.', 'static', [], $arguments))->arguments());
  }
}