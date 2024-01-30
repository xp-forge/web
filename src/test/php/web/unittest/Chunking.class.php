<?php namespace web\unittest;
/**
 * Chunked transfer encoding
 *
 * @see  https://en.wikipedia.org/wiki/Chunked_transfer_encoding
 * @see  https://tools.ietf.org/html/rfc7230#section-4.1
 */
trait Chunking {
  private static $CHUNKED = ['Transfer-Encoding' => 'chunked'];

  /**
   * Creates a chunked payload
   *
   * @param  string $payload
   * @param  int $length
   * @return string
   */
  private function chunked($payload, $length= 0xff) {
    $chunked= '';
    for ($i= 0; $i < strlen($payload); $i+= $length) {
      $chunk= substr($payload, $i, $length);
      $chunked.= sprintf("%x\r\n%s\r\n", strlen($chunk), $chunk);
    }
    return $chunked."0\r\n\r\n";
  }
}