<?php namespace web;

use lang\Value;
use util\Objects;

/**
 * Base user model. Can be extended by applications if necessary.
 *
 * @test  xp://web.unittest.UserTest
 */
class User implements Value {
  private $id, $roles;

  /**
   * Creates a new user instance
   *
   * @param  string $id
   * @param  string[] $roles
   */
  public function __construct($id, $roles= []) {
    $this->id= $id;
    $this->roles= $roles;
  }

  /** @return string */
  public function id() { return $this->id; }

  /** @return string[] */
  public function roles() { return $this->roles; }

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
    return nameof($this).'(id: "'.$this->id.'", roles= ['.implode(', ', $this->roles).'])';
  }

  /** @return string */
  public function hashCode() {
    return 'U'.md5($this->id);
  }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare([$this->id, $this->roles], [$value->id, $value->roles])
      : 1
    ;
  }
}