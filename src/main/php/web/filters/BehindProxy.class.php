<?php namespace web\filters;

use lang\IllegalArgumentException;
use util\URI;
use web\Filter;

/**
 * Rewrites URLs if behind a proxy
 *
 * @test  xp://web.unittest.filters.BehindProxyTest
 */
class BehindProxy implements Filter {

  /**
   * Creates a new instance given a map of the front-facing URL to the local path
   *
   * @param  [:string] $mapping e.g. `["http://remote.url/" => "/local.path"]`
   * @throws lang.IllegalArgumentException
   */
  public function __construct($mapping) {
    if (1 !== sizeof($mapping)) {
      throw new IllegalArgumentException('Expected a map in the form ["http://remote.url/" => "/local.path"]');
    }

    $this->remote= new URI(key($mapping));
    if ($this->remote->isRelative()) {
      throw new IllegalArgumentException('Remote URL must be absolute');
    }

    $this->replace= '#^'.rtrim(preg_quote(current($mapping), '#'), '/').'/#';
  }

  /**
   * Applies filter
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @param  web.filters.Invocation $invocation
   * @return var
   */
  public function filter($request, $response, $invocation) {
    $uri= $this->remote->using()
      ->path(preg_replace($this->replace, $this->remote->path(), $request->uri()->path()))
      ->create()
    ;

    return $invocation->proceed($request->rewrite($uri), $response);
  }
}