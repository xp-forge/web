<?php namespace web\filters;

use lang\IllegalArgumentException;
use util\Objects;

/**
 * Verifies origins
 *
 * @see  https://github.com/xp-forge/web/pull/131#pullrequestreview-4444923370
 * @test web.unittest.filters.OriginsTest
 */
class Origins {
  public $bases;
  public $ports= null;

  public function __construct($bases) {
    $this->bases= (array)$bases;
  }

  /** Returns origins matching localhost on `http` and `https` */
  public static function localhost(): self {
    return new self(['http://localhost', 'https://localhost']);
  }

  /**
   * Matches ports
   * 
   * - `null`: Directly match origins
   * - `'*'`: Match any port
   * - `80`: Match exactly port 80
   * - `[80, 443]`: Match ports 80 or 443
   * - `[null, 8080]`: Match absent port or port 8080
   * - `'8000..9000'`: Match anything in this port range
   * 
   * @param  ?string|array $ports
   */
  public function ports($ports): self {
    if (null === $ports) {
      $this->ports= null;
    } else {
      $this->ports= [];
      foreach (is_array($ports) ? $ports : [$ports] as $arg) {
        if (null === $arg) {
          $this->ports[]= fn($port) => null === $port;
        } else if ('*' === $arg) {
          $this->ports[]= fn($port) => true;
        } else if (is_numeric($arg)) {
          $cmp= (int)$arg;
          $this->ports[]= fn($port) => $cmp === $port;
        } else if (is_string($arg) && 2 === sscanf($arg, '%d..%d', $lo, $hi)) {
          $this->ports[]= fn($port) => $port >= $lo && $port <= $hi;
        } else {
          throw new IllegalArgumentException('Unexpected '.Objects::stringOf($arg));
        }
      }
    }
    return $this;
  }

  /** Retun whether a given origin matches */
  public function matches(string $origin): bool {
    if (null === $this->ports) return in_array($origin, $this->bases);

    // Check for empty origins or origins w/o scheme
    $s= strpos($origin, ':');
    if (false === $s) return false;

    // Check ports
    $p= strrpos($origin, ':', $s + 1);
    if (false === $p) {
      if (!in_array($origin, $this->bases)) return false;
      $port= null;
    } else {
      if (!in_array(substr($origin, 0, $p), $this->bases)) return false;
      $port= (int)substr($origin, $p + 1);
    }

    foreach ($this->ports as $check) {
      if ($check($port)) return true;
    }
    return false;
  }

  /** (...) overloading */
  public function __invoke($origin) {
    return $this->matches($origin ?? '') ? $origin : null;
  }
}