<?php namespace xp\web;

use io\File;
use io\streams\InputStream;
use web\io\Part;

class Upload extends Part implements InputStream {
  private $type, $source;
  private $handle= null;

  /**
   * Creates a new upload
   *
   * @param  string $name File name
   * @param  string $type Type as in Content-Type header
   * @param  string $source Source file in temporary upload directory
   */
  public function __construct($name, $type, $source) {
    parent::__construct($name);
    $this->type= $type;
    $this->source= $source;
  }

  /** @return int */
  public function kind() { return Part::FILE; }

  /**
   * Returns file name. Applies `basename()` on value transmitted to prevent
   * absolute and relative file names from causing problems.
   *
   * @param  bool $raw If set to true, returns name as transmitted
   * @return string
   */
  public function name($raw= false) {
    return $raw ? $this->name : basename($this->name);
  }

  /**
   * Returns value transmitted in Content-Type header
   *
   * @return string
   */
  public function type() { return $this->type; }

  /**
   * Returns bytes in a string
   *
   * @return string
   */
  public function bytes() {
    if (null === $this->handle) {
      $this->handle= new File($this->source);
      $this->handle->open(File::READ);
    } else {
      $this->handle->seek(0, SEEK_SET);
    }

    return $this->handle->read($this->handle->size());
  }

  /** @return int */
  public function available() {
    if (null === $this->handle) {
      $this->handle= new File($this->source);
      $this->handle->open(File::READ);
      return $this->handle->size();
    } else {
      return $this->handle->size() - $this->handle->tell();
    }
  }

  /**
   * Reads up to a specified number of bytes
   *
   * @param  int $limit
   * @return ?string NULL to indicate EOF
   */
  public function read($limit= 8192) {
    if (null === $this->handle) {
      $this->handle= new File($this->source);
      $this->handle->open(File::READ);
    }

    return (string)$this->handle->read($limit);
  }

  /** @return void */
  public function close() {
    if (null !== $this->handle) {
      $this->handle->close();
    }
  }

  /** @return string */
  public function toString() {
    return nameof($this).'("'.$this->name.'", type= '.$this->type.', source= '.$this->source.')';
  }
}