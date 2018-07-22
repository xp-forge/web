<?php namespace web;

use lang\Value;
use util\Objects;

/**
 * Base user model. Can be extended by applications if necessary.
 *
 * @test  xp://web.unittest.UserTest
 */
class User implements Value {
  private $id, $attributes, $roles;

  /**
   * Creates a new user instance
   *
   * @param  string $id
   * @param  [:string] $attributes
   * @param  string[] $roles
   */
  public function __construct($id, $attributes= [], $roles= []) {
    $this->id= $id;
    $this->attributes= $attributes;
    $this->roles= $roles;
  }

  /** @return string */
  public function id() { return $this->id; }

  /** @return string[] */
  public function roles() { return $this->roles; }

  /** @return [:string] */
  public function attributes() { return $this->attributes; }

  /**
   * Returns an attribute, or a default value if the attribute is not present
   *
   * @param  string $name
   * @param  ?string $default
   * @return string
   */
  public function attribute($name, $default= null) {
    return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
  }

  /**
   * Checks whether user is in a given role
   *
   * @param  string $role
   * @return bool
   */
  public function hasRole($role) {
    return in_array($role, $this->roles, true);
  }

  /** @return string */
  public function toString() {
    $s= nameof($this).'(id: "'.$this->id.'", roles= ['.implode(', ', $this->roles).'])';
    if ($this->attributes) {
      $s.= '@'.Objects::stringOf($this->attributes);
    }
    return $s;
  }

  /** @return string */
  public function hashCode() {
    return 'U'.md5($this->id);
  }

  /**
   * Comparison
   *
   * @param  var $that
   * @return int
   */
  public function compareTo($that) {
    return $that instanceof self
      ? Objects::compare([$this->id, $this->attributes, $this->roles], [$that->id, $that->attributes, $that->roles])
      : 1
    ;
  }
}