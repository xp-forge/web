<?php namespace xp\web;

/**
 * Wrapper for PHP's Server API ("SAPI").
 *
 * @see  http://php.net/reserved.variables.server
 * @see  http://php.net/wrappers.php
 * @see  http://php.net/php-sapi-name
 */
class SAPI extends \web\io\Output implements \web\io\Input {
  private $in= null;
  private $out;

  /** @return string */
  public function method() { return $_SERVER['REQUEST_METHOD']; }

  /** @return string */
  public function scheme() { return 'http'; }

  /** @return string */
  public function uri() { return $_SERVER['REQUEST_URI']; }

  /** @return [:string] */
  public function headers() { return getallheaders(); }

  /** @return string */
  public function readLine() {
    if (null === $this->in) {
      $this->in= fopen('php://input', 'rb');
    }
    return fgets($this->in, 8192);
  }

  /**
   * Read from request
   *
   * @param  int $length
   * @return string
   */
  public function read($length= -1) {
    if (null === $this->in) {
      $this->in= fopen('php://input', 'rb');
    }

    if (-1 === $length) {
      $r= '';
      while (!feof($this->in)) {
        $r.= fread($this->in, 8192);
      }
      return $r;
    } else {
      return fread($this->in, $length);
    }
  }

  /**
   * Start response
   *
   * @param  int $status
   * @param  string $message
   * @param  [:string] $headers
   * @return void
   */
  public function begin($status, $message, $headers) {
    if ('cgi' === PHP_SAPI || 'cgi-fcgi' === PHP_SAPI) {
      header('Status: '.$status.' '.$message);
    } else {
      header('HTTP/1.1 '.$status.' '.$message);
    }

    foreach ($headers as $name => $header) {
      header($name.': '.array_shift($header));
      foreach ($header as $value) {
        header($name.': '.$value, false);
      }
    }
    $this->out= '';
  }

  /**
   * Write to response
   *
   * @param  int $bytes
   * @return void
   */
  public function write($bytes) {
    $this->out.= $bytes;
  }

  /** @return void */
  public function flush() {
    echo $this->out;
    $this->out= '';
  }

  /** @return void */
  public function finish() {
    if ($this->in) {
      fclose($this->in);
    }
    echo $this->out;
  }
}