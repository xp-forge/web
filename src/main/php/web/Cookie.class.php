<?php namespace web;

use util\Date;
use util\TimeSpan;
use lang\IllegalArgumentException;

/**
 * A HTTP/1.1 Cookie
 *
 * @see   https://tools.ietf.org/html/rfc6265
 * @see   http://httpwg.org/http-extensions/draft-ietf-httpbis-cookie-same-site.html
 * @see   https://www.owasp.org/index.php/SameSite
 * @test  xp://web.unittest.CookieTest
 */
class Cookie {
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
   * @param  string $value Pass `null` to remove the cookie
   * @throws lang.IllegalArgumentException if value contains control characters or a semicolon
   */
  public function __construct($name, $value) {
    $this->name= $name;
    if (null === $value) {
      $this->value= '';
      $this->expires= time() - 86400 * 365;
      $this->maxAge= 0;
    } else if (preg_match('/[\x00-\x1F;]/', $value)) {
      throw new IllegalArgumentException('Cookie values cannot contain control characters or semicolons');
    } else {
      $this->value= $value;
    }
  }

  /**
   * Set expiration date
   *
   * @param  int|string|util.Date $expires
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
   * @param  int|util.TimeSpan $maxAge
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
   * @param  string $path
   * @return self
   */
  public function path($path) {
    $this->path= $path;
    return $this;
  }

  /**
   * Restricts to a given domain. Prefix with `.` to make valid for all subdomains
   *
   * @param  string $domain
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
   * @param  string $sameSite one of "Strict", "Lax" or null (use the latter to remove)
   * @return self
   */
  public function sameSite($sameSite) {
    $this->sameSite= $sameSite;
    return $this;
  }

  /** @return string */
  public function header() {
    return (
      $this->name.'='.$this->value.
      (null === $this->expires ? '' : '; Expires='.gmdate('D, d M Y H:i:s \G\M\T', $this->expires)).
      (null === $this->maxAge ? '' : '; Max-Age='.$this->maxAge).
      (null === $this->path ? '' : '; Path='.$this->path).
      (null === $this->domain ? '' : '; Domain='.$this->domain).
      (null === $this->sameSite ? '' : '; SameSite='.$this->sameSite).
      ($this->secure ? '; Secure' : '').
      ($this->httpOnly ? '; HttpOnly' : '')
    );
  }
}