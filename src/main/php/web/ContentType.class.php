<?php namespace web;

use lang\FormatException;

/**
 * Content-Type 
 *
 * @test  xp://web.unittest.ContentTypeTest
 * @see   https://tools.ietf.org/html/rfc2045#section-5.1
 */
class ContentType {
  private $mediaType;
  private $params= [];
  private $lookup= [];

  /**
   * Creates a content type instance; either from its string representation
   * when passed one argument, or from a media type and parameters.
   *
   * @param  string $input
   * @param  [:string] $params
   * @throws lang.FormatException
   */
  public function __construct($input, $params= null) {
    if (null !== $params) {
      $this->mediaType= $input;
      foreach ($params as $name => $value) {
        $this->params[$name]= $value;
        $this->lookup[strtolower($name)]= $name;
      }
    } else if (false === ($offset= strpos($input, ';'))) {
      $this->mediaType= $input;
    } else {
      $this->mediaType= substr($input, 0, $offset);
      $offset++;
      while (false !== ($p= strpos($input, '=', $offset))) {
        $name= ltrim(substr($input, $offset, $p - $offset), '; ');
        if ('"' === $input[$p + 1]) {
          $offset= $p + 2;
          do {
            if (false === ($offset= strpos($input, '"', $offset))) {
              throw new FormatException('Unclosed string in parameter "'.$name.'"');
            }
          } while ('\\' === $input[$offset++ - 1]);
          $value= strtr(substr($input, $p + 2, $offset - $p - 3), ['\"' => '"']);
        } else {
          $value= substr($input, $p + 1, strcspn($input, ';', $p) - 1);
          $offset= $p + strlen($value) + 1;
        }

        $this->params[$name]= $value;
        $this->lookup[strtolower($name)]= $name;
      }
    }
  }

  /** @return string */
  public function mediaType() { return $this->mediaType; }

  /** @return [:string] */
  public function params() { return $this->params; }

  /**
   * Gets a parameter by a given name
   *
   * @param  string $name
   * @param  string $default
   * @return string
   */
  public function param($name, $default= null) {
    $name= strtolower($name);
    return isset($this->lookup[$name]) ? $this->params[$this->lookup[$name]] : $default;
  }

  /**
   * Returns whether this content type matches a given pattern. Matching
   * of media type and subtype is ALWAYS case-insensitive.
   *
   * @param  string $pattern e.g. `text/plain` or `text/*`
   * @return bool
   */
  public function matches($pattern) {
    if (strstr($pattern, '*')) {
      return (bool)preg_match('#'.strtr($pattern, ['*' => '.+']).'#i', $this->mediaType);
    } else {
      return 0 === strncasecmp($pattern, $this->mediaType, strlen($pattern));
    }
  }

  /** @return string */
  public function toString() {
    $result= $this->mediaType;
    foreach ($this->params as $name => $value) {
      $result.= '; '.$name.'='.(strlen($value) === strcspn($value, '()<>@,;:\\".[]')
        ? $value
        : '"'.strtr($value, ['"' => '\"']).'"'
      );
    }
    return $result;
  }
}