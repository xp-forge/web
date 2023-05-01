<?php namespace web\unittest;

use io\{Path, Files, File};
use lang\{ElementNotFoundException, Environment as System};
use unittest\{Expect, Test, TestCase, Values};
use util\{Properties, PropertySource, RegisteredPropertySource};
use web\{Environment, Logging};

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
  public function logging_goes_to_console_by_defaul() {
    $this->assertEquals(Logging::of('-'), (new Environment('dev', '.', 'static', []))->logging());
  }

  #[Test]
  public function variable() {
    putenv('XP_TEST=abc');
    $this->assertEquals('abc', (new Environment('dev', '.', 'static', []))->variable('XP_TEST'));
  }

  #[Test, Values(['abc', ''])]
  public function set_variable($value) {
    $this->assertEquals($value, (new Environment('dev', '.', 'static', []))->export('test', $value)->variable('test'));
  }

  #[Test]
  public function unset_variable() {
    $this->assertNull((new Environment('dev', '.', 'static', []))->export('test', null)->variable('test'));
  }

  #[Test]
  public function tempDir() {
    $this->assertTrue(is_dir((new Environment('dev', '.', 'static', []))->tempDir()));
  }

  #[Test]
  public function tempDir_falls_back_to_sys_get_temp_dir() {
    $restore= [];
    foreach (['TEMP', 'TMP', 'TMPDIR', 'TEMPDIR'] as $variant) {
      if (!isset($_ENV[$variant])) continue;
      $restore[$variant]= $_ENV[$variant];
      unset($_ENV[$variant]);
    }

    try {
      $this->assertTrue(is_dir((new Environment('dev', '.', 'static', []))->tempDir()));
    } finally {
      $_ENV+= $restore;
    }
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

  #[Test]
  public function properties_from_file() {
    $file= new File(System::tempDir(), 'inject.ini');
    Files::write($file, "[test]\nkey=value");

    try {
      $environment= new Environment('dev', '.', 'static', [$file->getURI()]);
      $this->assertEquals('value', $environment->properties('inject')->readString('test', 'key'));
    } finally {
      $file->unlink();
    }
  }

  #[Test]
  public function properties_from_dir() {
    $file= new File(System::tempDir(), 'inject.ini');
    Files::write($file, "[test]\nkey=value");

    try {
      $environment= new Environment('dev', '.', 'static', [$file->getPath()]);
      $this->assertEquals('value', $environment->properties('inject')->readString('test', 'key'));
    } finally {
      $file->unlink();
    }
  }

  #[Test, Values('expansions')]
  public function property_expansions($value, $expanded) {
    $file= new File(System::tempDir(), 'inject.ini');
    Files::write($file, "[test]\nkey=".$value);

    try {
      $environment= new Environment('dev', '.', 'static', [$file->getURI()]);
      $this->assertEquals($expanded($environment), $environment->properties('inject')->readString('test', 'key'));
    } finally {
      $file->unlink();
    }
  }

  #[Test]
  public function composite_properties() {
    $environment= new Environment('dev', '.', 'static', [
      new RegisteredPropertySource('inject', new Properties('prod/inject.ini')),
      new RegisteredPropertySource('inject', new Properties('default/inject.ini')),
    ]);
    $this->assertEquals(2, $environment->properties('inject')->length());
  }

  #[Test]
  public function arguments_empty_by_default() {
    $this->assertEquals([], (new Environment('dev', '.', 'static', []))->arguments());
  }

  #[Test, Values([[[]], [['test', 'value']]])]
  public function arguments($arguments) {
    $this->assertEquals($arguments, (new Environment('dev', '.', 'static', [], $arguments))->arguments());
  }
}