<?php namespace web\io;

interface Output {

  public function begin($status, $message, $headers);

  public function write($bytes);

}