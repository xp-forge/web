<?php namespace web;

abstract class Session {

  public abstract function id();

  public abstract function register($name, $value);

  public abstract function value($name);

  public abstract function remove($name);

  public abstract function destroy();

  public function cache($name, $provider, $ttl= null, $time= null) {
    $time??= time();
    if ($cached= $this->value($name)) {
      [$stored, $value]= $cached;
      if (null === $ttl || $stored < $time + $ttl) return $value;
    }

    $value= $provider();
    $this->register($name, [$time, $value]);
    return $value;
  }
}