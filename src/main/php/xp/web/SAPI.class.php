<?php namespace xp\web;

use web\io\Input;
use web\io\Output;

/**
 * Wrapper for PHP's Server API ("SAPI").
 *
 * @see  http://php.net/reserved.variables.server
 * @see  http://php.net/wrappers.php
 * @see  http://php.net/php-sapi-name
 */
class SAPI extends Output implements Input {
  private $in= null;
  private $out;

  static function __static() {
    if (!function_exists('getallheaders')) {
      function getallheaders() {
        $headers= [];
        foreach ($_SERVER as $name => $value) {
          if (0 === strncmp($name, 'HTTP_', 5)) {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))]= $value;
          }
        }
        return $headers;
      }
    }
  }

  /** @return string */
  public function method() { return $_SERVER['REQUEST_METHOD']; }

  /** @return string */
  public function scheme() {
    return (isset($_SERVER['HTTPS']) && in_array(strtolower($_SERVER['HTTPS']), ['on', '1'])) ? 'https' : 'http';
  }

  /** @return version */
  public function version() {
    sscanf($_SERVER['SERVER_PROTOCOL'], 'HTTP/%[0-9.]', $version);
    return $version;
  }

  /** @return string */
  public function uri() { return $_SERVER['REQUEST_URI']; }

  /** @return [:string] */
  public function headers() {
    yield 'Remote-Addr' => $_SERVER['REMOTE_ADDR'];
    foreach (getallheaders() as $name => $value) {
      yield $name => $value;
    }
  }

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

  /** Creates a new per-request SAPI I/O instance */
  public function __construct() {
    ob_start(function($buffer) {
      fputs(STDOUT, $buffer);
    });
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
    unset($headers['Host'], $headers['Date']);
    foreach ($headers as $name => $header) {
      header($name.': '.array_shift($header));
      foreach ($header as $value) {
        header($name.': '.$value, false);
      }
    }
    ob_end_clean();
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