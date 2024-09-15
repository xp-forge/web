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
  private $array= null;
  private static $nesting;

  static function __static() {
    self::$nesting= ini_get('max_input_nesting_level');
  }

  /**
   * Creates an instance from a given name and value
   * 
   * @param  string $name
   * @param  var $value
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
   * @see    https://www.php.net/parse_str
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

    // Trim leading spaces, replace '.' and ' ' inbetween with underscores, see
    // https://github.com/php/php-src/blob/php-8.4.0beta5/main/php_variables.c#L133
    if (false === ($p= strpos($name, '['))) {
      $self= new self(urldecode(strtr(ltrim($name, ' '), '. ', '__')));
    } else if (substr_count($name, '[') <= self::$nesting) {
      $self= new self(urldecode(strtr(ltrim(substr($name, 0, $p), ' '), '. ', '__')));
      $self->array= urldecode(substr($name, $p));
    } else {
      throw new FormatException('Cannot parse '.$name.' (nesting level > '.self::$nesting.')');
    }

    $self->value= urldecode($encoded);
    return $self;
  }

  /**
   * Merge this parameter with a given pointer
   *
   * @param  var $ptr
   * @return void
   */
  public function merge(&$ptr) {
    if ($this->array) {
      $o= 0;
      $l= strlen($this->array);
      do {
        $p= strcspn($this->array, ']', $o);
        $token= substr($this->array, $o + 1, $p - 1);

        if ('' === $token) {
          $ptr= &$ptr[];
        } else {
          $ptr= &$ptr[$token];
        }

        $o+= $p + 1;
      } while ($o < $l);
    }

    $ptr= $this->value;
  }

  /** @return int */
  public function kind() { return Part::PARAM; }

  /** @return var */
  public function value() {
    if ($this->array) {
      $this->merge($value);
      return $value;
    } else {
      return $this->value;
    }
  }

  /** @return string */
  public function toString() {
    return nameof($this).'("'.$this->name.$this->array.'", value= '.Objects::stringOf($this->value).')';
  }
}