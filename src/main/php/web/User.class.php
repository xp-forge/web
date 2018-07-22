<?php namespace web;

/**
 * Base user model. Can be extended by applications if necessary.
 *
 * @test  xp://web.unittest.UserTest
 */
class User {
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
}