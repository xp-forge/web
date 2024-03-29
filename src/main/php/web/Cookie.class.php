<?php namespace web;

use lang\{Value, IllegalArgumentException};
use util\{Date, TimeSpan};

/**
 * A HTTP/1.1 Cookie. Values are encoded using URL encoding.
 *
 * @see   https://tools.ietf.org/html/rfc6265
 * @see   http://httpwg.org/http-extensions/draft-ietf-httpbis-cookie-same-site.html
 * @see   https://www.owasp.org/index.php/SameSite
 * @see   https://developer.mozilla.org/en-US/docs/Web/API/document/cookie
 * @test  web.unittest.CookieTest
 */
class Cookie implements Value {
  private $name, $value;

  private $expires= null;
  private $maxAge= null;
  private $path= null;
  private $domain= null;
  private $secure= false;
  private $httpOnly= true;
  private $sameSite= 'Lax';

  /**
   * Creates a new cookie
   *
   * @param  string $name
   * @param  ?string $value Pass `null` to remove the cookie
   * @throws lang.IllegalArgumentException if the cookie name contains illegal characters
   */
  public function __construct($name, $value) {
    if (strcspn($name, "=,; \t\r\n\013\014") < strlen($name)) {
      throw new IllegalArgumentException('Cookie names cannot contain any of [=,; \t\r\n\013\014]');
    }

    $this->name= $name;
    if (null === $value) {
      $this->value= '';
      $this->expires= time() - 86400 * 365;
      $this->maxAge= 0;
    } else {
      $this->value= $value;
    }
  }

  /** @return string */
  public function name() { return $this->name; }

  /** @return ?string */
  public function value() { return $this->value; }

  /** @return [:var] */
  public function attributes() {
    return [
      'expires'  => $this->expires,
      'maxAge'   => $this->maxAge,
      'path'     => $this->path,
      'domain'   => $this->domain,
      'secure'   => $this->secure,
      'httpOnly' => $this->httpOnly,
      'sameSite' => $this->sameSite,
    ];
  }

  /**
   * Set expiration date
   *
   * @param  ?int|string|util.Date $expires
   * @return self
   */
  public function expires($expires) {
    if ($expires instanceof Date) {
      $this->expires= $expires->getTime();
    } else if (is_string($expires)) {
      $this->expires= strtotime($expires);
    } else {
      $this->expires= $expires;
    }
    return $this;
  }

  /**
   * Set maximum age in seconds.
   *
   * @param  ?int|util.TimeSpan $maxAge
   * @return self
   */
  public function maxAge($maxAge) {
    if ($maxAge instanceof TimeSpan) {
      $this->maxAge= $maxAge->getSeconds();
    } else {
      $this->maxAge= $maxAge;
    }
    return $this;
  }

  /**
   * Restricts to a given path. Use `/` for all paths on a given domain
   *
   * @param  ?string $path
   * @return self
   */
  public function path($path) {
    $this->path= $path;
    return $this;
  }

  /**
   * Restricts to a given domain. Prefix with `.` to make valid for all subdomains
   *
   * @param  ?string $domain
   * @return self
   */
  public function domain($domain) {
    $this->domain= $domain;
    return $this;
  }

  /**
   * Switch whether to only transmit via secure connections (HTTPS).
   *
   * @param  bool $secure
   * @return self
   */
  public function secure($secure= true) {
    $this->secure= $secure;
    return $this;
  }

  /**
   * Switch whether to only transmit via HTTP only, making it inaccessible to JavaScript.
   *
   * @param  bool $secure
   * @return self
   */
  public function httpOnly($httpOnly= true) {
    $this->httpOnly= $httpOnly;
    return $this;
  }

  /**
   * Switch whether to only transmit only to same site; preventing CSRF
   *
   * @param  ?string $sameSite one of "Strict", "Lax" or null (use the latter to remove)
   * @return self
   */
  public function sameSite($sameSite) {
    $this->sameSite= $sameSite;
    return $this;
  }

  /** @return string */
  public function header() {
    return (
      $this->name.'='.rawurlencode($this->value).
      (null === $this->expires ? '' : '; Expires='.Headers::date($this->expires)).
      (null === $this->maxAge ? '' : '; Max-Age='.$this->maxAge).
      (null === $this->path ? '' : '; Path='.$this->path).
      (null === $this->domain ? '' : '; Domain='.$this->domain).
      (null === $this->sameSite ? '' : '; SameSite='.$this->sameSite).
      ($this->secure ? '; Secure' : '').
      ($this->httpOnly ? '; HttpOnly' : '')
    );
  }

  /** @return string */
  public function hashCode() { return crc32($this->header()); }

  /** @return string */
  public function toString() { return nameof($this).'<'.$this->header().'>'; }

  /**
   * Compare
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? $this->header() <=> $value->header() : 1;
  }
}