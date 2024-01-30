<?php namespace web\io;

use lang\FormatException;
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
   * Creates an instance from a given name and value
   * 
   * @param  string $name
   * @param  var $valu
   */
  public static function from($name, $value) {
    $self= new self($name);
    $self->value= $value;
    return $self;
  }

  /**
   * Parse parameters from the `name` parameter of the Content-Disposition
   * header and its payload, including array handling.
   *
   * @param  string $name
   * @param  iterable $chunks
   * @return self
   * @throws lang.FormatException When input variable nesting level exceeded
   */
  public static function parse($name, $chunks) {
    $encoded= '';
    foreach ($chunks as $chunk) {
      $encoded.= $chunk;
    }
    parse_str($name.'='.urlencode($encoded), $param);

    // Check if the input variable nesting level was exceeded
    if (empty($param)) {
      $e= new FormatException('Cannot parse '.$name);
      \xp::gc(__FILE__);
      throw $e;
    }

    $self= new self(key($param));
    $self->value= current($param);
    return $self;
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