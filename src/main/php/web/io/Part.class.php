<?php namespace web\io;

use lang\Value;

/** 
 * Base class for parameters, uploads and incomplete parts (PHP SAPI) as
 * well as streams (Standalone Server) as returned by `Multipart::parts()`.
 *
 * @see   xp://web.Multipart
 * @see   xp://web.io.Parts
 */
abstract class Part implements Value {
  const FILE = 0;
  const PARAM = 1;
  const INCOMPLETE = 2;

  protected $name;

  /** @param string $name */
  public function __construct($name) { $this->name= $name; }

  /** @return int */
  public abstract function kind();

  /** @return string */
  public function name() { return $this->name; }

  /** @return string */
  public function toString() { return nameof($this).'('.$this->name.')'; }

  /** @return string */
  public function hashCode() { return spl_object_hash($this); }

  /**
   * Compares this part to a given value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $this === $value ? 0 : 1;
  }
}