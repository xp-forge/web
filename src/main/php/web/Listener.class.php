<?php namespace web;

interface Listener {

  public function message($connection, $message);
}