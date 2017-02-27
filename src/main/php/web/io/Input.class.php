<?php namespace web\io;

interface Input {

  /** @return string */
  public function method();

  /** @return string */
  public function scheme();

  /** @return sring */
  public function uri();

  /** @return iterable */
  public function headers();
}