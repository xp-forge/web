<?php namespace web;

use IteratorAggregate, Traversable;
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
 * @deprecated See https://github.com/xp-forge/web/issues/113#issuecomment-2118673725
 * @see   xp://web.Request#dispatch
 */
class Dispatch implements IteratorAggregate {
  private $uri;

  /** @param util.URI|string $uri */
  public function __construct($uri) {
    $this->uri= $uri instanceof URI ? $uri : new URI($uri);
  }

  /** @return util.URI */
  public function uri() { return $this->uri; }

  /** @return iterable */
  public function getIterator(): Traversable { yield 'dispatch' => $this->uri; }
}