<?php namespace web\unittest\logging;

use io\TempFile;
use lang\IllegalArgumentException;
use unittest\{Expect, Test, TestCase};
use web\io\{TestInput, TestOutput};
use web\logging\ToFile;
use web\{Error, Request, Response};

class ToFileTest extends TestCase {
  private $temp;

  /** @return void */
  public function setUp() {
    $this->temp= new TempFile('sink');
  }

  /** @return void */
  public function tearDown() {
    if ($this->temp->exists()) {
      $this->temp->setPermissions(0600);
      $this->temp->unlink();
    }
  }

  #[Test]
  public function can_create() {
    new ToFile($this->temp);
  }

  #[Test]
  public function file_created_during_constructor_call() {
    new ToFile($this->temp);
    $this->assertTrue($this->temp->exists());
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function raises_error_if_file_cannot_be_written_to() {
    $this->temp->setPermissions(0000);
    new ToFile($this->temp);
  }

  #[Test]
  public function log() {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    (new ToFile($this->temp))->log($req, $res, null);

    $this->assertNotEquals(0, $this->temp->size());
  }

  #[Test]
  public function log_with_error() {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    (new ToFile($this->temp))->log($req, $res, new Error(404, 'Test'));

    $this->assertNotEquals(0, $this->temp->size());
  }
}