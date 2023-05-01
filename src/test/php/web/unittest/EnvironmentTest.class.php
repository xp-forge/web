<?php namespace web\unittest;

use io\{File, Files, Path};
use lang\{ElementNotFoundException, Environment as System};
use test\{Assert, Expect, Test, Values};
use util\{Properties, PropertySource, RegisteredPropertySource};
use web\{Environment, Logging};

class EnvironmentTest {

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
    Assert::equals('dev', (new Environment('dev', '.', 'static', []))->profile());
  }

  #[Test]
  public function webroot() {
    Assert::equals(new Path('.'), (new Environment('dev', '.', 'static', []))->webroot());
  }

  #[Test]
  public function docroot() {
    Assert::equals(new Path('static'), (new Environment('dev', '.', 'static', []))->docroot());
  }

  #[Test]
  public function logging_goes_to_console_by_defaul() {
    Assert::equals(Logging::of('-'), (new Environment('dev', '.', 'static', []))->logging());
  }

  #[Test]
  public function variable() {
    putenv('XP_TEST=abc');
    Assert::equals('abc', (new Environment('dev', '.', 'static', []))->variable('XP_TEST'));
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
    Assert::true(is_dir((new Environment('dev', '.', 'static', []))->tempDir()));
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
      Assert::true(is_dir((new Environment('dev', '.', 'static', []))->tempDir()));
    } finally {
      $_ENV+= $restore;
    }
  }

  #[Test]
  public function path() {
    $environment= new Environment('dev', '.', 'static', []);
    Assert::equals(
      new Path($environment->webroot(), 'src/main/handlebars'),
      $environment->path('src/main/handlebars')
    );
  }

  #[Test]
  public function non_existant_variable() {
    putenv('XP_TEST');
    Assert::null((new Environment('dev', '.', 'static', []))->variable('XP_TEST'));
  }

  #[Test, Expect(ElementNotFoundException::class)]
  public function non_existant_properties() {
    (new Environment('dev', '.', 'static', []))->properties('inject');
  }

  #[Test]
  public function optional_non_existant_properties() {
    Assert::null((new Environment('dev', '.', 'static', []))->properties('inject', true));
  }

  #[Test, Values([true, false])]
  public function properties($optional) {
    $prop= new Properties('inject.ini');
    $environment= new Environment('dev', '.', 'static', [new RegisteredPropertySource('inject', $prop)]);
    Assert::equals($prop, $environment->properties('inject', $optional));
  }

  #[Test]
  public function properties_from_file() {
    $file= new File(System::tempDir(), 'inject.ini');
    Files::write($file, "[test]\nkey=value");

    try {
      $environment= new Environment('dev', '.', 'static', [$file->getURI()]);
      Assert::equals('value', $environment->properties('inject')->readString('test', 'key'));
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
      Assert::equals('value', $environment->properties('inject')->readString('test', 'key'));
    } finally {
      $file->unlink();
    }
  }

  #[Test, Values(from: 'expansions')]
  public function property_expansions($value, $expanded) {
    $file= new File(System::tempDir(), 'inject.ini');
    Files::write($file, "[test]\nkey=".$value);

    try {
      $environment= new Environment('dev', '.', 'static', [$file->getURI()]);
      Assert::equals($expanded($environment), $environment->properties('inject')->readString('test', 'key'));
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
    Assert::equals(2, $environment->properties('inject')->length());
  }

  #[Test]
  public function arguments_empty_by_default() {
    Assert::equals([], (new Environment('dev', '.', 'static', []))->arguments());
  }

  #[Test, Values([[[]], [['test', 'value']]])]
  public function arguments($arguments) {
    Assert::equals($arguments, (new Environment('dev', '.', 'static', [], $arguments))->arguments());
  }
}