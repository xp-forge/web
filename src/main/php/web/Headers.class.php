<?php namespace web;

use lang\FormatException;
use util\Date;

/**
 * Parses headers
 *
 * @see   https://en.wikipedia.org/wiki/List_of_HTTP_header_fields
 * @see   https://en.wikipedia.org/wiki/Content_negotiation
 * @test  web.unittest.HeadersTest
 */
abstract class Headers {

  /**
   * Formats a date for use in headers
   *
   * @param  ?int|util.Date $arg
   * @return string
   */
  public static function date($arg= null) {
    return gmdate('D, d M Y H:i:s \G\M\T', $arg instanceof Date ? $arg->getTime() : $arg);
  }

  /**
   * Parses a given input string and returns the parsed values
   *
   * @param  string $input
   * @return var
   */
  public function parse($input) {
    $offset= 0;
    return $this->next($input, $offset);
  }

  /**
   * Parser implementation
   *
   * @param  string $input
   * @param  &int $offset
   * @return var
   */
  protected abstract function next($input, &$offset);

  /**
   * Returns a new parser for headers with multiple values
   *
   * `Accept-Language: en, de`
   * `Accept-Encoding: gzip, br`
   *
   * @param  self $parse
   * @return self
   */
  public static function values($parse) {
    return new class($parse) extends Headers {
      private $parse;

      public function __construct($parse) { $this->parse= $parse; }

      protected function next($input, &$offset) {
        $values= [];
        do {
          $values[]= $this->parse->next($input, $offset);
          $c= $input[$offset - 1] ?? null;
          if (',' === $c) continue;
          if (null === $c) break;

          throw new FormatException('Expected ",", have "'.$c.'"');
        } while ($c);
        return $values;
      }
    };
  }

  /**
   * Returns a new parser for parameterized headers, e.g.:
   *
   * `Content-Type: text/html;charset=utf-8`
   * `Refresh: 5; url=http://www.w3.org/pub/WWW/People.html`
   *
   * @return self
   */
  public static function parameterized() {
    return new class() extends Headers {
      protected function next($input, &$offset) {
        $s= strcspn($input, ',;', $offset);
        $value= ltrim(substr($input, $offset, $s), ' ');
        $offset+= $s + 1;

        $c= $input[$offset - 1] ?? null;
        $parameters= ';' === $c ? Headers::pairs()->next($input, $offset) : [];

        return new Parameterized($value, $parameters);
      }
    };
  }

  /**
   * Returns a new parser for quality-factor headers, e.g.:
   *
   * `Accept-Encoding: deflate, gzip;q=1.0, *;q=0.5`
   *
   * @return self
   */
  public static function qfactors() {
    return new class() extends Headers {
      protected function next($input, &$offset) {
        $weighted= [];
        $q= 1.0;
        do {
          $s= strcspn($input, ',;', $offset);
          $value= ltrim(substr($input, $offset, $s), ' ');
          $offset+= $s + 1;

          $c= $input[$offset - 1] ?? null;
          if (';' === $c) {
            $weighted[$value]= (float)(Headers::pairs()->next($input, $offset)['q'] ?? $q);
            $c= $input[$offset - 1] ?? null;
          } else {
            $weighted[$value]= $q;
          }

          $q-= 0.0001;
        } while ($c);

        arsort($weighted, SORT_NUMERIC);
        return $weighted;
      }
    };
  }

  /**
   * Returns a new parser for headers with key/value pairs
   *
   * `Forwarded: for=192.0.2.43;proto=http, for=198.51.100.17`
   *
   * @return self
   */
  public static function pairs() {
    return new class() extends Headers {
      protected function next($input, &$offset) {
        $parameters= [];
        $l= strlen($input);
        do {
          $s= strcspn($input, '=', $offset);
          if ($offset + $s >= $l) {
            throw new FormatException('Could not find "="');
          }

          $name= trim(substr($input, $offset, $s));
          $offset+= $s + 1;

          if ('"' === $input[$offset]) {
            $p= $offset + 1;
            do {
              if (false === ($p= strpos($input, '"', $p))) {
                throw new FormatException('Unclosed string in parameter "'.$name.'"');
              }
            } while ('\\' === $input[$p++ - 1]);

            // Ignore the spec stating `"`, `\r` and `\n` should be percent-encoded for
            // consistency with PHP, see https://github.com/php/php-src/issues/8206
            $parameters[$name]= strtr(substr($input, $offset + 1, $p - $offset - 2), ['\"' => '"']);
            $offset= $p + 1;
          } else {
            $s= strcspn($input, ',;', $offset);
            $parameters[$name]= substr($input, $offset, $s);
            $offset+= $s + 1;
          }
        } while ($offset < $l && ';' === $input[$offset - 1]);

        return $parameters;
      }
    };
  }
}