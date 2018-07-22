<?php namespace web\unittest;

use unittest\TestCase;
use web\User;

class UserTest extends TestCase {

  #[@test]
  public function can_create() {
    new User('admin');
  }

  #[@test]
  public function can_create_with_attributes() {
    new User('admin', ['name' => 'Teh Admin']);
  }

  #[@test]
  public function can_create_with_roles() {
    new User('admin', [], ['admins', 'users']);
  }

  #[@test]
  public function id() {
    $this->assertEquals('admin', (new User('admin'))->id());
  }

  #[@test]
  public function attributes() {
    $this->assertEquals(['name' => 'Teh Admin'], (new User('admin', ['name' => 'Teh Admin']))->attributes());
  }

  #[@test]
  public function attribute() {
    $this->assertEquals('Teh Admin', (new User('admin', ['name' => 'Teh Admin']))->attribute('name'));
  }

  #[@test]
  public function non_existant_attribute() {
    $this->assertNull((new User('admin'))->attribute('name'));
  }

  #[@test]
  public function non_existant_attribute_uses_default() {
    $this->assertEquals('Default', (new User('admin'))->attribute('name', 'Default'));
  }

  #[@test]
  public function roles() {
    $this->assertEquals(['admins', 'users'], (new User('admin', [], ['admins', 'users']))->roles());
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
    $this->assertEquals($expected, (new User('admin', [], ['admins', 'users']))->hasRole($role));
  }

  #[@test]
  public function string_representation() {
    $this->assertEquals('web.User(id: "admin", roles= [])', (new User('admin'))->toString());
  }

  #[@test]
  public function string_representation_with_attribute() {
    $this->assertEquals(
      "web.User(id: \"admin\", roles= [])@[\n".
      "  name => \"Teh Admin\"\n".
      "]",
      (new User('admin', ['name' => 'Teh Admin']))->toString()
    );
  }

  #[@test]
  public function string_representation_with_attributes() {
    $this->assertEquals(
      "web.User(id: \"admin\", roles= [])@[\n".
      "  id => 6100\n".
      "  name => \"Teh Admin\"\n".
      "]",
      (new User('admin', ['id' => 6100, 'name' => 'Teh Admin']))->toString()
    );
  }

  #[@test]
  public function string_representation_with_roles() {
    $this->assertEquals(
      'web.User(id: "admin", roles= [admins, users])',
      (new User('admin', [], ['admins', 'users']))->toString()
    );
  }
}