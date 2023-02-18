<?php namespace web\unittest\io;

use io\OperationNotSupportedException;
use test\{Assert, Expect, Test};
use web\io\{Incomplete, Part};

class IncompleteTest {

  #[Test]
  public function can_create() {
    new Incomplete('upload', UPLOAD_ERR_INI_SIZE);
  }

  #[Test]
  public function kind() {
    Assert::equals(Part::INCOMPLETE, (new Incomplete('upload', UPLOAD_ERR_INI_SIZE))->kind());
  }

  #[Test]
  public function error() {
    Assert::equals('ERR_INI_SIZE', (new Incomplete('upload', UPLOAD_ERR_INI_SIZE))->error());
  }

  #[Test]
  public function string_representation() {
    Assert::equals(
      'web.io.Incomplete("upload", error= ERR_INI_SIZE)',
      (new Incomplete('upload', UPLOAD_ERR_INI_SIZE))->toString()
    );
  }

  #[Test, Expect(OperationNotSupportedException::class)]
  public function cannot_access_bytes() {
    (new Incomplete('upload', UPLOAD_ERR_INI_SIZE))->bytes();
  }

  #[Test, Expect(OperationNotSupportedException::class)]
  public function cannot_transmit() {
    $it= (new Incomplete('upload', UPLOAD_ERR_INI_SIZE))->transmit('./uploads');
    while ($it->valid()) {
      $it->next();
    }
  }

  #[Test, Expect(OperationNotSupportedException::class)]
  public function cannot_read_bytes() {
    (new Incomplete('upload', UPLOAD_ERR_INI_SIZE))->read();
  }

  #[Test]
  public function close_is_a_noop() {
    Assert::null((new Incomplete('upload', UPLOAD_ERR_INI_SIZE))->close());
  }
}