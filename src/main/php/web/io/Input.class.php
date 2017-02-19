<?php namespace web\io;

interface Input {

  /** @return iterable */
  public function headers();
}