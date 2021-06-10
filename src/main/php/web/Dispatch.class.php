<?php namespace web;

use util\URI;

/**
 * Dispatches a request; performing an internal redirect.
 * 
 * Return instances of this class from a handler *before* the response
 * has been flushed, as follows:
 *
 * ```php
 * function($req, $res) {
 *   return $req->dispatch('/home');
 * }
 * ```
 *
 * @see   xp://web.Request#dispatch
 */
class Dispatch implements \IteratorAggregate {
  private $uri;

  /** @param util.URI|string $uri */
  public function __construct($uri) {
    $this->uri= $uri instanceof URI ? $uri : new URI($uri);
  }

  /** @return util.URI */
  public function uri() { return $this->uri; }

  /** @return iterable */
  public function getIterator() { yield $this; }
}