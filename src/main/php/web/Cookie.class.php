<?php namespace web;

use util\Date;

/**
 * A HTTP/1.1 Cookie
 *
 * @see  https://tools.ietf.org/html/rfc6265
 * @see  http://httpwg.org/http-extensions/draft-ietf-httpbis-cookie-same-site.html
 * @see  https://www.owasp.org/index.php/SameSite
 */
class Cookie {
  private $name, $value;

  private $expires= null;
  private $maxAge= null;
  private $path= null;
  private $domain= null;
  private $secure= false;
  private $httpOnly= true;
  private $sameSite= null;

  /**
   * Creates a new cookie
   *
   * @param  string $name
   * @param  string $value
   */
  public function __construct($name, $value) {
    $this->name= $name;
    $this->value= $value;
  }

  /**
   * Set expiration date
   *
   * @param  int|string|util.Date $expires
   * @return self
   */
  public function expires($expires) {
    if (null === $expires) {
      $this->expires= null;
    } else if ($expires instanceof Date) {
      $this->expires= $expires;
    } else {
      $this->expires= new Date($expires);
    }
    return $this;
  }

  /**
   * Set maximum age in seconds. Use negative values to expire immediately.
   *
   * @param  int $maxAge
   * @return self
   */
  public function maxAge($maxAge) {
    $this->maxAge= $maxAge;
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
   * @param  string $sameSite one of `Strict` or `Lax`.
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
      (null === $this->expires ? '' : '; Expires='.gmdate('D, d M Y H:i:s \G\M\T', $this->expires->getTime())).
      (null === $this->maxAge ? '' : '; Max-Age='.$this->maxAge).
      (null === $this->path ? '' : '; Path='.$this->path).
      (null === $this->domain ? '' : '; Domain='.$this->domain).
      (null === $this->sameSite ? '' : '; SameSite='.$this->sameSite).
      ($this->secure ? '; Secure' : '').
      ($this->httpOnly ? '; HttpOnly' : '')
    );
  }
}