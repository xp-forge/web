<?php namespace web\filters;

use web\Filter;
use peer\net\InetAddressFactory;
use peer\net\Network;

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
  private $trusted= [];

  /**
   * Creates a new instance given a list of trusted proxy addresses or networks
   *
   * @param  string|string[] $trusted
   */
  public function __construct($trusted) {
    $addresses= new InetAddressFactory();

    $list= is_array($trusted) ? $trusted : explode(',', $trusted);
    foreach ($list as $arg) {
      if (2 === sscanf($arg, '%[^/]/%d$', $addr, $mask)) {
        $network= new Network($addresses->parse($addr), $mask);
        $f= function($addr) use($addresses, $network) {
          return $network->contains($addresses->parse($addr));
        };
      } else {
        $address= $addresses->parse($arg);
        $f= function($addr) use($addresses, $address) {
          return 0 === $address->compareTo($addresses->parse($addr));
        };
      }
      $this->trusted[$arg]= $f;
    }
  }

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
   * Returns whether the given remote address is trusted
   *
   * @param  string $remote
   * @return bool
   */
  public function trusts($remote) {
    foreach ($this->trusted as $trusted) {
      if ($trusted($remote)) return true;
    }
    return false;
  }

  /**
   * Return last value in a potentially comma-separated header
   *
   * @param  string $header
   * @return string
   */
  private function last($header) {
    if (null === $header) {
      return null;
    } else if (false === ($p= strrpos($header, ','))) {
      return $header;
    } else {
      return ltrim(substr($header, $p + 1), ' ');
    }
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
    if (($forwarded= $this->last($request->header('X-Forwarded-Host'))) && $this->trusts($request->header('Remote-Addr'))) {
      $uri= $request->uri();
      $rewrite= $this->path;
      $request->rewrite($uri->using()
        ->host($forwarded)
        ->scheme($this->protocol ?: $this->last($request->header('X-Forwarded-Proto', 'https')))
        ->port($this->last($request->header('X-Forwarded-Port', null)))
        ->path($rewrite ? $rewrite($uri->path()) : $uri->path())
        ->create()
      );
    }

    return $invocation->proceed($request, $response);
  }
}