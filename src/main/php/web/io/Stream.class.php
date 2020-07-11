<?php namespace web\io;

use io\streams\InputStream;

/**
 * A file as part of a multipart request which can be accessed as a stream
 *
 * @see  xp://web.io.Parts
 */
class Stream extends Part implements InputStream {
  private $type, $chunks;
  private $bytes= null;

  /**
   * Creates a new instance
   *
   * @param  string $name The `filename` parameter of the Content-Disposition header
   * @param  string $type The value of the Content-Type header
   * @param  iterable $chunks
   */
  public function __construct($name, $type, $chunks) {
    parent::__construct($name);
    $this->type= $type;
    $this->chunks= $chunks;
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
   * Returns bytes in a string, lazily loaded from the underlying stream.
   * Use the `InputStream` API to read data without storing it to memory.
   *
   * @return string
   */
  public function bytes() {
    if (null === $this->bytes) {
      $this->bytes= '';
      foreach ($this->chunks as $chunk) {
        $this->bytes.= $chunk;
      }
    }
    return $this->bytes;
  }

  /** @return int */
  public function available() {
    return $this->chunks->valid() ? 1 : 0;
  }

  /**
   * Reads up to a specified number of bytes
   *
   * @param  int $limit
   * @return ?string NULL to indicate EOF
   */
  public function read($limit= 8192) {
    if ($this->chunks->valid()) {
      $bytes= $this->chunks->current();
      $this->chunks->next();
      return $bytes;
    }

    return null;
  }

  /** @return void */
  public function close() {
    // NOOP
  }

  /** @return string */
  public function toString() {
    return nameof($this).'("'.$this->name.'", type= '.$this->type.')';
  }
}