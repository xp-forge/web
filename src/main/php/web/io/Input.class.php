<?php namespace web\io;

interface Input {

  /** @return string */
  public function version();

  /** @return string */
  public function method();

  /** @return string */
  public function scheme();

  /** @return sring */
  public function uri();

  /** @return iterable */
  public function headers();

  /** @return string */
  public function readLine();

  /**
   * Reads a given number of bytes
   *
   * @param  int $length Pass -1 to read all
   * @return string
   */
  public function read($length= -1);
}