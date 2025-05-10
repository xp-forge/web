<?php namespace web;

/** @test web.unittest.SessionTest */
abstract class Session {

  /**
   * Returns session ID
   *
   * @return string
   */
  public abstract function id();

  /**
   * Returns session expiration time
   *
   * @return int
   */
  public abstract function expires();

  /**
   * Registers a value by a given a name in this session
   *
   * @param  string $name
   * @param  var $value
   * @return void
   */
  public abstract function register($name, $value);

  /**
   * Returns a previously registered value by its given name
   *
   * @param  string $name
   * @param  var $default
   * @return var
   */
  public abstract function value($name, $default= null);

  /**
   * Removes a previously registered value by its given name
   *
   * @param  string $name
   * @return void
   */
  public abstract function remove($name);

  /**
   * Destroys this session
   *
   * @return void
   */
  public abstract function destroy();

}