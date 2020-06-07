<?php namespace web;

class Parameterized {
  private $value, $params;

  /**
   * Creates a new instance
   *
   * @param  string $value
   * @param  [:string] $params
   */
  public function __construct($value, array $params) {
    $this->value= $value;
    $this->params= $params;
  }

  /** @return string */
  public function value() { return $this->value; }

  /** @return [:string] */
  public function params() { return $this->params; }

  /**
   * Gets a parameter by its name, returning a default value if it's
   * not present.
   *
   * @param  string $name
   * @param  var $default
   * @return var
   */
  public function param($name, $default= null) {
    return $this->params[$name] ?? $default;
  }
}