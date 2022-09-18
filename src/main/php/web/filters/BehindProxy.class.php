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
  private $remote, $replace;

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
    $uri= $request->uri();
    return $invocation->proceed(
      $request->rewrite($this->remote->using()
        ->path(preg_replace($this->replace, $this->remote->path(), $uri->path()))
        ->query($uri->query())
        ->create()
      ),
      $response
    );
  }
}