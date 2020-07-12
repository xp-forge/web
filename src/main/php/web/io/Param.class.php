<?php namespace web\io;

/**
 * A parameter as part of a multipart request
 *
 * @see  xp://web.io.Parts
 */
class Param extends Part {
  private $value= '';

  /**
   * Creates a new instance
   *
   * @param  string $name The `name` parameter of the Content-Disposition header
   * @param  iterable $chunks
   */
  public function __construct($name, $chunks) {
    parent::__construct($name);
    foreach ($chunks as $chunk) {
      $this->value.= $chunk;
    }
  }

  /** @return int */
  public function kind() { return Part::PARAM; }

  /** @return string */
  public function value() { return $this->value; }

  /** @return string */
  public function toString() {
    return nameof($this).'("'.$this->name.'", value= "'.$this->value.'")';
  }
}