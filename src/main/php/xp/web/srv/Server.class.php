<?php namespace xp\web\srv;

interface Server {

  /**
   * Serve requests
   *
   * @param  string $source
   * @param  string $profile
   * @param  io.Path $webroot
   * @param  io.Path $docroot
   * @param  string[] $config
   * @param  string[] $args
   * @param  string $logging
   */
  public function serve($source, $profile, $webroot, $docroot, $config, $args, $logging);
}