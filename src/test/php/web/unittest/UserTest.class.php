<?php namespace web\unittest;

use unittest\TestCase;
use web\User;

class UserTest extends TestCase {

  #[@test]
  public function can_create() {
    new User('admin');
  }

  #[@test]
  public function can_create_with_roles() {
    new User('admin', ['admins', 'users']);
  }

  #[@test]
  public function id() {
    $this->assertEquals('admin', (new User('admin'))->id());
  }

  #[@test]
  public function roles() {
    $this->assertEquals(['admins', 'users'], (new User('admin', ['admins', 'users']))->roles());
  }

  #[@test]
  public function roles_can_be_omitted() {
    $this->assertEquals([], (new User('admin'))->roles());
  }

  #[@test, @values([
  #  ['admins', true], ['users', true],
  #  ['ADMINS', false], ['admin', false],
  #  [null, false],
  #])]
  public function hasRole($role, $expected) {
    $this->assertEquals($expected, (new User('admin', ['admins', 'users']))->hasRole($role));
  }
}