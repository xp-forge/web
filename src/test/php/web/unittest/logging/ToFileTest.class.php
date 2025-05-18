<?php namespace web\unittest\logging;

use io\TempFile;
use lang\IllegalArgumentException;
use test\{After, Before, Assert, Expect, Test};
use web\Error;
use web\logging\ToFile;

class ToFileTest {
  private $temp;

  #[Before]
  public function setup() {
    $this->temp= new TempFile('sink');
  }

  #[After]
  public function cleanup() {
    $this->temp->exists() && $this->temp->unlink();
  }

  #[Test]
  public function can_create() {
    new ToFile($this->temp);
  }

  #[Test]
  public function target() {
    Assert::equals(
      'web.logging.ToFile('.$this->temp->getURI().')',
      (new ToFile($this->temp))->target()
    );
  }

  #[Test]
  public function file_created_during_constructor_call() {
    new ToFile($this->temp);
    Assert::true($this->temp->exists());
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function raises_error_if_file_cannot_be_written_to() {
    $this->temp->setPermissions(0000);
    try {
      new ToFile($this->temp);
    } finally {
      $this->temp->setPermissions(0600);
    }
  }

  #[Test]
  public function log() {
    (new ToFile($this->temp))->log(200, 'GET', '/', []);
    Assert::notEquals(0, $this->temp->size());
  }

  #[Test]
  public function log_with_error() {
    (new ToFile($this->temp))->log(404, 'GET', '/not-found', ['error' => new Error(404, 'Test')]);
    Assert::notEquals(0, $this->temp->size());
  }
}