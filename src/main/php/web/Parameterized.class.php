<?php namespace web;

class Parameterized {
  private $value, $params;

  /**
   * Creates a new instance
   *
   * @param  string $value
   * @param  [:var] $params
   */
  public function __construct($value, array $params= []) {
    $this->value= $value;
    $this->params= $params;
  }

  /**
   * Passes parameters and optionally, their ASCII equivalents.
   *
   * @param  string $name
   * @param  string|[:string] $value
   * @param  ?string $equivalent
   * @return self
   */
  public function with($name, $value, $equivalent= null) {
    $name= rtrim($name, '*');
    null === $equivalent || $this->params[$name]= $equivalent;

    if (is_array($value)) {
      $this->params[$name.'*']= 1 === sizeof($value)
        ? ['lang' => current($value), 'value' => key($value)]
        : $value
      ;
    } else if (isset($equivalent) || preg_match('/[\x7f-\xff]/', $value)) {
      $this->params[$name.'*']= ['lang' => null, 'value' => $value];
    } else {
      $this->params[$name]= $value;
    }

    return $this;
  }

  /** @return string */
  public function value() { return $this->value; }

  /** @return [:var] */
  public function params() { return $this->params; }

  /**
   * Gets a parameter by its name, returning a default value if it's not
   * present. Prefers the RFC 8187 encoded parameter ending with `*`.
   *
   * @param  string $name
   * @param  var $default
   * @return var
   */
  public function param($name, $default= null) {
    if ($param= $this->params[$name.'*'] ?? null) {
      return $param['value'];
    } else {
      return $this->params[$name] ?? $default;
    }
  }

  /** @return string */
  public function __toString() {
    $s= $this->value;
    foreach ($this->params as $name => $value) {
      $s.= '; '.$name;
      if (is_array($value)) {
        $s.= "=UTF-8'{$value['lang']}'".rawurlencode($value['value']);
      } else if (strspn($value, Headers::ATTR_CHAR) < strlen($value)) {
        $s.= '="'.strtr($value, ['\\' => '\\\\', '"' => '\\"']).'"';
      } else {
        $s.= '='.$value;
      }
    }
    return $s;
  }
}