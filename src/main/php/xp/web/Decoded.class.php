<?php namespace xp\web;

use util\Objects;
use web\io\Part;

/**
 * A decoded parameter as part of a multipart request
 *
 * @see  xp://web.io.Parts
 */
class Decoded extends Part {
  private $value;

  /**
   * Creates a new instance
   *
   * @param  string $name The `name` parameter of the Content-Disposition header
   * @param  mixed $value
   */
  public function __construct($name, $value) {
    parent::__construct($name);
    $this->value= $value;
  }

  /**
   * Append this parameter to a given list of parameters and return the new list
   *
   * @param  [:var] $params
   * @return [:var]
   */
  public function append($params) {
    return array_merge_recursive($params, $this->value);
  }

  /** @return int */
  public function kind() { return Part::PARAM; }

  /** @return mixed */
  public function value() { return $this->value; }

  /** @return string */
  public function toString() {
    return nameof($this).'("'.$this->name.'", value= '.Objects::stringOf($this->value).'")';
  }
}