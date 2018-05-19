<?php namespace web\filters;

use web\Filter;

/**
 * Rewrites request URI if behind a reverse proxy
 *
 * @see  https://github.com/xp-forge/web/pull/40
 * @see  https://en.wikipedia.org/wiki/Reverse_proxy
 * @see  http://httpd.apache.org/docs/2.4/mod/mod_proxy.html#x-headers
 * @see  https://www.nginx.com/resources/wiki/start/topics/examples/forwarded/
 * @see  https://stackoverflow.com/questions/19084340/real-life-usage-of-the-x-forwarded-host-header
 * @test xp://web.unittest.filters.BehindProxyTest
 */
class BehindProxy implements Filter {
  private $path= null;
  private $protocol= null;

  /**
   * Force the use of a given protocol
   *
   * @param  string $protocol One of "http", "https"
   * @return self
   */
  public function using($protocol) {
    $this->protocol= $protocol;
    return $this;
  }

  /**
   * Prefix paths with a given base
   *
   * @param  string $base
   * @return self
   */
  public function prefixed($base) {
    $prefix= rtrim($base, '/');
    $this->path= function($path) use($prefix) { return $prefix.$path; };
    return $this;
  }

  /**
   * Strip paths of a given base
   *
   * @param  string $base
   * @return self
   */
  public function stripping($base) {
    $strip= '#^'.rtrim($base, '/').'#';
    $this->path= function($path) use($strip) { return preg_replace($strip, '', $path) ?: '/'; };
    return $this;
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
    if ($forwarded= $request->header('X-Forwarded-Host')) {
      $rewrite= $this->path;
      $request->rewrite($uri->using()
        ->host($forwarded)
        ->scheme($this->protocol ?: $request->header('X-Forwarded-Proto', 'https'))
        ->port($request->header('X-Forwarded-Port', null))
        ->path($rewrite ? $rewrite($uri->path()) : $uri->path())
        ->create()
      );
    }

    return $invocation->proceed($request, $response);
  }
}