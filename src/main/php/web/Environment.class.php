<?php namespace web;

class Environment {

  public function __construct($profile, $webroot, $docroot, $config) {
    $this->profile= $profile;
    $this->webroot= $webroot;
    $this->docroot= $docroot;
    $this->config= $config;
  }

  public function profile() { return $this->profile; }

  public function webroot() { return $this->webroot; }

  public function docroot() { return $this->docroot; }

}