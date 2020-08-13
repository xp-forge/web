<?php namespace xp\web;

use io\streams\{InputStream, OutputStream};
use io\{File, Folder};
use lang\IllegalArgumentException;
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
   * Returns file name
   *
   * @param  bool $raw Has no effect, PHP strips filename of path components for us.
   * @return string
   * @see    https://github.com/php/php-src/blob/PHP-7.4.0/main/rfc1867.c#L1141
   */
  public function name($raw= false) {
    return $this->name;
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

  /**
   * Transfers this stream to a given target.
   *
   * @param  io.Path|io.Folder|io.streams.OutputStream|string $target
   * @return int Number of bytes written
   * @throws lang.IllegalArgumentException if filename is invalid
   * @throws io.IOException
   */
  public function transfer($target) {

    // If we are passed a stream, transfer the source file's contents
    if ($target instanceof OutputStream) {
      $this->handle= new File($this->source);
      $this->handle->open(File::READ);

      $written= 0;
      do {
        $chunk= $this->handle->read();
        $target->write($chunk);
        $written+= strlen($chunk);
      } while (!$this->handle->eof());

      return $written;
    }

    // Use filesystem I/O to move the already stored file
    $file= new File($this->source);
    if ($target instanceof File) {
      $to= $target;
    } else if ($target instanceof Folder) {
      $to= new File($target, $this->name());
    } else if (is_string($target) && (0 === strlen($target) || false !== strpos($target, "\0"))) {
      throw new IllegalArgumentException('Invalid filename "'.addcslashes($target, "\0..\37!\177..\377").'"');
    } else if (is_dir($target)) {
      $to= new File($target, $this->name());
    } else {
      $to= new File($target);
    }

    $file->move($to);
    return $file->size();
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
  public function source() {
    return $this->source;
  }

  /** @return string */
  public function toString() {
    return nameof($this).'("'.$this->name.'", type= '.$this->type.', source= '.$this->source.')';
  }
}