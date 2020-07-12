<?php namespace web\io;

use lang\FormatException;
use util\Objects;
use web\Headers;

/**
 * Handles parsing `multipart/form-data` from a given input stream.
 *
 * @see   https://tools.ietf.org/html/rfc7578
 * @see   https://stackoverflow.com/a/4762734 Boundaries in payload
 * @see   xp://web.Multipart
 * @test  xp://web.unittest.io.PartsTest
 */
class Parts implements \IteratorAggregate {
  private $in, $boundary;
  private $buffer= '';

  /**
   * Reads parts from a given input stream 
   *
   * @param  io.streams.InputStream $in
   * @param  string $boundary
   */
  public function __construct($in, $boundary) {
    $this->in= $in;
    $this->boundary= $boundary;
  }

  /** @return string */
  private function line() {
    while (false === ($p= strpos($this->buffer, "\r\n")) && $this->in->available()) {
      $this->buffer.= $this->in->read(8192);
    }

    // If we hit EOF before a CLRF was found, simply return entire buffer
    if (false === $p) {
      $line= $this->buffer;
      $this->buffer= '';
    } else {
      $line= substr($this->buffer, 0, $p);
      $this->buffer= substr($this->buffer, $p + 2);
    }
    return $line;
  }

  /**
   * Returns chunks in a given part
   *
   * @return iterable
   */
  private function part() {
    $delimiter= "\r\n--".$this->boundary;
    $n= strlen($delimiter);
    do {

      // Yield chunks as long as no "\r" is encountered
      while (false === ($p= strpos($this->buffer, "\r")) && $this->in->available()) {
        yield $this->buffer;
        $this->buffer= $this->in->read(8192);
      }

      // Found beginning of delimiter, read enough bytes to be able to decide
      while ($p + $n >= strlen($this->buffer) && $this->in->available()) {
        $this->buffer.= $this->in->read(8192);
      }

      // Delimiter found in buffer, end reading
      if ($p < strlen($this->buffer) && 0 === substr_compare($this->buffer, $delimiter, $p, $n)) {
        $part= substr($this->buffer, 0, $p);
        $this->buffer= substr($this->buffer, $p + 2);
        yield $part;
        return;
      }

      $part= substr($this->buffer, 0, $p + 1);
      $this->buffer= substr($this->buffer, $p + 1);
      yield $part;
    } while (strlen($this->buffer) > 0);
  }

  /**
   * Returns parts from a multipart/form-data request
   *
   * @return iterable
   */
  public function getIterator() {
    $last= '--'.$this->boundary.'--';
    $boundary= $this->line();

    // Handle border case when no parts have been submitted. Swallow an empty line here: Some
    // browsers will send this malformed version when using an empty JavaScript FormData object.
    if ('' === $boundary) $boundary= $this->line();
    if ($last === $boundary) return;

    $next= '--'.$this->boundary;
    $parameterized= Headers::parameterized();
    while ($next === $boundary) {
      $headers= [];
      while ($line= $this->line()) {
        sscanf($line, "%[^:]: %[^\r]", $name, $value);
        $headers[strtolower($name)]= $value;
      }

      // RFC section 4.2.: Each part MUST contain a Content-Disposition header field
      if (null === ($disposition= $headers['content-disposition'] ?? null)) {
        throw new FormatException('Malformed or truncated part, headers: '.Objects::stringOf($headers));
      }

      $type= $parameterized->parse($disposition);
      $name= $type->param('name');
      $filename= $type->param('filename', null);
      $chunks= $this->part();
      if (null === $filename) {
        yield $name => new Param($name, $chunks);
      } else if ('' === $filename) {
        yield $name => new Incomplete($name, UPLOAD_ERR_NO_FILE);
      } else {
        yield $name => new Stream($filename, $headers['content-type'] ?? 'application/octet-stream', $chunks);
      }

      // Silently discard any unconsumed chunks before continuing to read
      while ($chunks->valid()) $chunks->next();

      $boundary= $this->line();
      if ($last === $boundary) return;
    }

    throw new FormatException('Expected boundary "'.$next.'", have "'.$boundary.'"');
  }
}