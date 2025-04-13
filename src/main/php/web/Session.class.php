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
   * @return var
   */
  public abstract function value($name);

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

  /**
   * Retrieves a cached value by its given name. If no previous value exists,
   * or if it has expired, the provider function is invoked.
   *
   * @param  string $name
   * @param  function(): var $provider
   * @param  ?int $ttl Number of seconds until the value expires, NULL for never.
   * @param  ?int $time
   * @return void
   */
  public function cache(string $name, callable $provider, $ttl= null, $time= null) {
    $time ?? $time= time();
    if ($cached= $this->value($name)) {
      list($stored, $value)= $cached;
      if (null === $ttl || $time <= $stored + $ttl) return $value;
    }

    $value= $provider();
    $this->register($name, [$time, $value]);
    return $value;
  }
}