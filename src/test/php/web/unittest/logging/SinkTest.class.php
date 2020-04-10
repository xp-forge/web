<?php namespace web\unittest\logging;

use io\TempFile;
use unittest\TestCase;
use web\logging\{Sink, ToAllOf, ToConsole, ToFile, ToFunction};

class SinkTest extends TestCase {

  #[@test, @values([[null], [[]]])]
  public function no_logging($arg) {
    $this->assertNull(Sink::of($arg));
  }

  #[@test]
  public function logging_to_console() {
    $this->assertInstanceOf(ToConsole::class, Sink::of('-'));
  }

  #[@test]
  public function logging_to_function() {
    $this->assertInstanceOf(ToFunction::class, Sink::of(function($req, $res, $error) { }));
  }

  #[@test]
  public function logging_to_file() {
    $t= new TempFile('log');
    try {
      $this->assertInstanceOf(ToFile::class, Sink::of($t));
    } finally {
      $t->unlink();
    }
  }

  #[@test]
  public function logging_to_file_by_name() {
    $t= new TempFile('log');
    try {
      $this->assertInstanceOf(ToFile::class, Sink::of($t->getURI()));
    } finally {
      $t->unlink();
    }
  }

  #[@test]
  public function logging_to_all_of() {
    $t= new TempFile('log');
    try {
      $this->assertInstanceOf(ToAllOf::class, Sink::of(['-', $t]));
    } finally {
      $t->unlink();
    }
  }

  #[@test]
  public function logging_to_all_of_flattened_when_only_one_argument_passed() {
    $this->assertInstanceOf(ToConsole::class, Sink::of(['-']));
  }
}