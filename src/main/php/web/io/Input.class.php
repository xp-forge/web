<?php namespace web\io;

interface Input {

  /** @return string */
  public function method();

  /** @return sring */
  public function uri();

  /** @return iterable */
  public function headers();
}