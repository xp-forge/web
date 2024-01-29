<?php namespace web\io;

use util\Objects;

/**
 * A parameter as part of a multipart request
 *
 * @see  web.io.Parts
 * @test web.unittest.ParamsTest
 */
class Param extends Part {
  private $value;

  /**
   * Creates a new instance
   *
   * @param  string $name
   * @param  var $value
   */
  public function __construct($name, $value) {
    parent::__construct($name);
    $this->value= $value;
  }

  /**
   * Parse parameters from the `name` parameter of the Content-Disposition
   * header and its payload, including array handling.
   *
   * @param  string $name
   * @param  iterable $chunks
   * @return self
   */
  public static function parse($name, $chunks) {
    $encoded= '';
    foreach ($chunks as $chunk) {
      $encoded.= $chunk;
    }
    parse_str($name.'='.urlencode($encoded), $param);
    return new self(key($param), current($param));
  }

  /**
   * Append this parameter to a given list of parameters and return the new list
   *
   * @param  [:var] $params
   * @return [:var]
   */
  public function append($params) {
    return array_merge_recursive($params, [$this->name => $this->value]);
  }

  /** @return int */
  public function kind() { return Part::PARAM; }

  /** @return var */
  public function value() { return $this->value; }

  /** @return string */
  public function toString() {
    return nameof($this).'("'.$this->name.'", value= '.Objects::stringOf($this->value).')';
  }
}