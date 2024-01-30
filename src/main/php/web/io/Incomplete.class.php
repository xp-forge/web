<?php namespace web\io;

use io\OperationNotSupportedException;
use io\streams\InputStream;
use web\io\Part;

/**
 * An incomplete part, e.g. when the limit set via `upload_max_filesize`
 * is exceeded or the uploaded file cannot be stored. Will raise exceptions
 * if used as a file.
 *
 * @test  web.unittest.io.IncompleteTest
 * @see   https://www.php.net/manual/en/features.file-upload.errors.php
 */
class Incomplete extends Part implements InputStream {
  private $error;

  /**
   * Creates a new incomplete part
   *
   * @param  string $name
   * @param  int $error
   */
  public function __construct($name, $error) {
    static $errors= [
      UPLOAD_ERR_INI_SIZE   => 'ERR_INI_SIZE',
      UPLOAD_ERR_FORM_SIZE  => 'ERR_FORM_SIZE',
      UPLOAD_ERR_PARTIAL    => 'ERR_PARTIAL',
      UPLOAD_ERR_NO_FILE    => 'ERR_NO_FILE',
      UPLOAD_ERR_NO_TMP_DIR => 'ERR_NO_TMP_DIR',
      UPLOAD_ERR_CANT_WRITE => 'ERR_CANT_WRITE',
      UPLOAD_ERR_EXTENSION  => 'ERR_EXTENSION',
    ];

    parent::__construct($name);
    $this->error= $errors[$error] ?? '#'.$error;
  }

  /** @return int */
  public function kind() { return Part::INCOMPLETE; }

  /** @return string */
  public function error() { return $this->error; }

  /** @return io.IOException */
  private function notSupported() {
    return new OperationNotSupportedException('Cannot read from incomplete part (error= '.$this->error.')');
  }

  /** @return string */
  public function bytes() { throw $this->notSupported(); }

  /**
   * Transmits this stream to a given target.
   *
   * @param  io.Path|io.Folder|io.streams.OutputStream|string $target
   * @return iterable
   * @throws lang.IllegalArgumentException if filename is invalid
   * @throws io.IOException
   */
  public function transmit($target) { throw $this->notSupported(); yield; }

  /** @return int */
  public function available() { throw $this->notSupported(); }

  /**
   * Reads up to a specified number of bytes
   *
   * @param  int $limit
   * @return ?string NULL to indicate EOF
   */
  public function read($limit= 8192) { throw $this->notSupported(); }

  /** @return void */
  public function close() {
    // NOOP
  }

  /** @return string */
  public function toString() { return nameof($this).'("'.$this->name.'", error= '.$this->error.')'; }
}