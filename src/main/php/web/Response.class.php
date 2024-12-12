<?php namespace web;

use io\Channel;
use io\streams\InputStream;
use lang\{IllegalStateException, IllegalArgumentException};
use web\io\WriteChunks;

/**
 * Response
 *
 * @test  xp://web.unittest.ResponseTest
 */
class Response {
  private $output;
  private $flushing= [];
  private $flushed= false;
  private $status= 200;
  private $message= 'OK';
  private $cookies= [];
  private $headers= [];
  public $trace= [];

  /** @param web.io.Output $output */
  public function __construct($output= null) {
    $this->output= $output;
  }

  /**
   * Sets status code and optionally a message
   *
   * @param  int $status
   * @param  ?string $message
   * @return void
   */
  public function answer($status, $message= null) {
    $this->status= $status;
    $this->message= $message ?: Status::message($status);
  }

  /**
   * Sets a cookie
   *
   * @param  web.Cookie $cookie
   * @param  bool $append Append header if already existant
   * @return void
   */
  public function cookie(Cookie $cookie, $append= false) {
    if ($append) {
      $this->cookies[]= $cookie;
    } else {
      $this->cookies[$cookie->name()]= $cookie;
    }
  }

  /**
   * Sets a header
   *
   * @param  string $name
   * @param  string $value Pass NULL to remove the header
   * @param  bool $append Append header if already existant
   * @return void
   */
  public function header($name, $value, $append= false) {
    if (null === $value) {
      unset($this->headers[$name]);
    } else if ($append) {
      $this->headers[$name][]= $value;
    } else if (is_array($value)) {
      $this->headers[$name]= [];
      foreach ($value as $v) {
        $this->headers[$name][]= (string)$v;
      }
    } else {
      $this->headers[$name]= [(string)$value];
    }
  }

  /**
   * Adds a named value to the trace, which will show up in the log file
   *
   * @param  string $name
   * @param  var $value
   * @return void
   */
  public function trace($name, $value) {
    $this->trace[$name]= $value;
  }

  /** @return int */
  public function status() { return $this->status; }

  /** @return string */
  public function message() { return $this->message; }

  /** @return web.io.Output */
  public function output() { return $this->output; }

  /** @return bool */
  public function flushed() { return $this->flushed; }

  /** @return web.Cookie[] */
  public function cookies() { return array_values($this->cookies); }

  /** @return [:string|string[]] */
  public function headers() {
    $r= [];
    foreach ($this->headers as $name => $header) {
      $r[$name]= 1 === sizeof($header) ? $header[0] : $header;
    }
    return $r;
  }

  /** @param web.io.Output $output */
  private function begin($output) {
    foreach ($this->flushing as $function) {
      $function($this);
    }
    $output->begin($this->status, $this->message, $this->cookies
      ? array_merge($this->headers, ['Set-Cookie' => array_map(function($c) { return $c->header(); }, $this->cookies)])
      : $this->headers
    );
    $this->flushed= true;
  }

  /**
   * Passes a function to call before flushing the response
   *
   * @param  function(self): void $function
   * @return self
   */
  public function flushing(callable $function) {
    $this->flushing[]= $function;
    return $this;
  }

  /**
   * Flushes response
   *
   * @return void
   * @throws lang.IllegalStateException
   */
  public function flush() {
    if ($this->flushed) {
      throw new IllegalStateException('Response already flushed');
    }

    $this->begin($this->output);
  }

  /**
   * Ends reponse, ensuring headers are sent and output is closed.
   *
   * Takes care of signalling zero length content if no transmission
   * has occured.
   *
   * @return void
   */
  public function end() {
    if (!$this->flushed) {
      if (!isset($this->headers['Content-Length']) && !isset($this->headers['Transfer-Encoding'])) {
        $this->headers['Content-Length']= [0];
      }

      $this->begin($this->output);
    }
    $this->output->close();
  }

  /**
   * Returns a stream to write on
   *
   * @param  int $size If omitted, uses chunked transfer encoding
   * @return io.streams.OutputStream
   * @throws lang.IllegalStateException
   */
  public function stream($size= null) {
    if ($this->flushed) {
      throw new IllegalStateException('Response already flushed');
    }

    if (null === $size) {
      $output= $this->output->stream();
    } else {
      $this->headers['Content-Length']= [$size];
      $output= $this->output;
    }

    $this->begin($output);
    return $output;
  }

  /**
   * Sends intermediate status and headers
   *
   * @param  int $status
   * @param  ?string $message
   * @param  [:string|string[]] $headers
   * @return void
   */
  public function hint($status, $message= null, $headers= []) {
    $pass= [];
    foreach ($this->headers as $name => $header) {
      $pass[$name]= is_array($header) ? $header : [$header];
    }
    foreach ($headers as $name => $header) {
      $pass[$name]= is_array($header) ? $header : [$header];
    }
    $this->output->begin($status, $message ?: Status::message($status), $pass);
  }

  /**
   * Transmits a given source to the output asynchronously.
   *
   * @param  io.Channel|io.streams.InputStream $source
   * @param  string $mediaType
   * @param  int $size If omitted, uses chunked transfer encoding
   * @return iterable
   * @throws lang.IllegalArgumentException
   */
  public function transmit($source, $mediaType= 'application/octet-stream', $size= null) {
    if ($source instanceof InputStream) {
      $in= $source;
    } else if ($source instanceof Channel) {
      $in= $source->in();
    } else {
      throw new IllegalArgumentException('Expected either a channel or an input stream, have '.typeof($source));
    }

    $this->headers['Content-Type']= [$mediaType];
    $out= $this->stream($size);
    try {
      while ($in->available()) {
        yield 'write' => null;
        $out->write($in->read());
      }
    } finally {
      $out->close();
      $in->close();
    }
  }

  /**
   * Sends some content
   *
   * @param  string $content
   * @param  string $mediaType
   * @return void
   */
  public function send($content, $mediaType= 'text/html') {
    $this->headers['Content-Type']= [$mediaType];

    $out= $this->stream(strlen($content));
    try {
      $out->write($content);
    } finally {
      $out->close();
    }
  }
}