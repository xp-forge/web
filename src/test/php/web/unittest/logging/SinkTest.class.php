<?php namespace web\unittest\logging;

use io\TempFile;
use test\{Assert, Test, Values};
use util\log\LogCategory;
use web\logging\{Sink, ToAllOf, ToCategory, ToConsole, ToFile, ToFunction};

class SinkTest {

  #[Test, Values([[null], [[]]])]
  public function no_logging($arg) {
    Assert::null(Sink::of($arg));
  }

  #[Test]
  public function logging_to_console() {
    Assert::instance(ToConsole::class, Sink::of('-'));
  }

  #[Test]
  public function logging_to_function() {
    Assert::instance(ToFunction::class, Sink::of(function($status, $method, $uri, $hints) { }));
  }

  #[Test]
  public function logging_to_category() {
    Assert::instance(ToCategory::class, Sink::of(new LogCategory('test')));
  }

  #[Test]
  public function logging_to_file() {
    $t= new TempFile('log');
    try {
      Assert::instance(ToFile::class, Sink::of($t));
    } finally {
      $t->unlink();
    }
  }

  #[Test]
  public function logging_to_file_by_name() {
    $t= new TempFile('log');
    try {
      Assert::instance(ToFile::class, Sink::of($t->getURI()));
    } finally {
      $t->unlink();
    }
  }

  #[Test]
  public function logging_to_all_of() {
    $t= new TempFile('log');
    try {
      Assert::instance(ToAllOf::class, Sink::of(['-', $t]));
    } finally {
      $t->unlink();
    }
  }

  #[Test]
  public function logging_to_all_of_flattened_when_only_one_argument_passed() {
    Assert::instance(ToConsole::class, Sink::of(['-']));
  }
}