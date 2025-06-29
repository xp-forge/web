<?php namespace xp\web;

use web\io\{Buffered, Input, Output, Param, Parts, ReadStream, ReadLength, WriteChunks, Incomplete};

/**
 * Wrapper for PHP's Server API ("SAPI").
 *
 * @see  http://www.faqs.org/rfcs/rfc3875.html
 * @see  https://php.net/reserved.variables.server
 * @see  https://php.net/wrappers.php
 * @see  https://php.net/php-sapi-name
 */
class SAPI extends Output implements Input {
  private $in= null;
  private $incoming;
  private $out;

  static function __static() {
    if (!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'wb'));
    if (!function_exists('getallheaders')) {
      function getallheaders() {
        $headers= [];
        foreach (['CONTENT_TYPE' => 'Content-Type', 'CONTENT_LENGTH' => 'Content-Length'] as $meta => $header) {
          if (isset($_SERVER[$meta])) {
            $headers[$header]= $_SERVER[$meta];
          }
        }
        foreach ($_SERVER as $name => $value) {
          if (0 === strncmp($name, 'HTTP_', 5)) {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))]= $value;
          }
        }
        return $headers;
      }
    }
  }

  /** Creates a new per-request SAPI I/O instance */
  public function __construct($incoming= null) {
    $this->incoming= $incoming;
    ob_start(function($buffer) {
      fputs(STDOUT, $buffer);
    });
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
  public function resource() { return $_SERVER['REQUEST_URI']; }

  /** @return [:string] */
  public function headers() {
    yield 'Remote-Addr' => $_SERVER['REMOTE_ADDR'];
    foreach (getallheaders() as $name => $value) {

      if (null !== $this->incoming) {
        // Already determined whether an incoming payload is available
      } else if (0 === strncasecmp($name, 'Transfer-Encoding', 17) && 'chunked' === $value) {
        $this->incoming= new ReadStream($this);
      } else if (0 === strncasecmp($name, 'Content-Length', 14)) {
        $this->incoming= new ReadLength($this, (int)$value);
      }

      yield $name => $value;
    }
  }

  /** @return ?io.streams.InputStream */
  public function incoming() { return $this->incoming; }

  /**
   * Returns parts from a multipart/form-data request. Includes all request parameters
   * up-front as there is no way to know the order in which they were originally passed.
   *
   * @see    https://www.php.net/manual/en/reserved.variables.files.php
   * @see    https://www.php.net/manual/en/ini.core.php#ini.sect.file-uploads
   * @param  string $boundary
   * @return iterable
   */
  public function parts($boundary) {

    // Use superglobals pre-populated by PHP SAPI for POST requests. Otherwise,
    // fall back to parsing request "by hand". We could use request_parse_body(),
    // see https://wiki.php.net/rfc/rfc1867-non-post, but would need a fallback
    // for PHP < 8.4, so we might as well just stream the parts.
    if ('POST' === $_SERVER['REQUEST_METHOD']) {
      foreach ($_REQUEST as $name => $value) {
        yield $name => Param::from($name, $value);
      }
      foreach ($_FILES as $name => $file) {
        if (is_array($file['error'])) {
          $name.= '[]';
          foreach ($file['error'] as $i => $error) {
            if (UPLOAD_ERR_OK === $error) {
              yield $name => new Upload($file['name'][$i], $file['type'][$i], $file['tmp_name'][$i]);
            } else {
              yield $name => new Incomplete($file['name'][$i], $error);
            }
          }
        } else if (UPLOAD_ERR_OK === $file['error']) {
          yield $name => new Upload($file['name'], $file['type'], $file['tmp_name']);
        } else {
          yield $name => new Incomplete($file['name'], $file['error']);
        }
      }
    } else {
      yield from new Parts($this->incoming, $boundary);
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

  /**
   * Uses chunked TE for HTTP/1.1, buffering for HTTP/1.0 or when using
   * Apache and FastCGI, which is broken.
   *
   * @return web.io.Output
   * @see    https://tools.ietf.org/html/rfc2068#section-19.7.1
   * @see    https://bz.apache.org/bugzilla/show_bug.cgi?id=53332
   */
  public function stream() {
    $buffered= (
      isset($_SERVER['GATEWAY_INTERFACE']) &&
      stristr($_SERVER['GATEWAY_INTERFACE'], 'CGI') &&
      stristr($_SERVER['SERVER_SOFTWARE'], 'Apache')
    ) || $_SERVER['SERVER_PROTOCOL'] < 'HTTP/1.1';

    return $buffered ? new Buffered($this) : new WriteChunks($this);
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
    flush();
  }

  /** @return void */
  public function finish() {
    if ($this->in) {
      fclose($this->in);
    }
    echo $this->out;
  }

  /** End output buffering if begin() was not called */
  public function __destruct() {
    isset($this->out) || ob_end_clean();
  }
}