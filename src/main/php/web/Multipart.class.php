<?php namespace web;

use web\io\Part;

/**
 * Multipart requests consist of multiple parts, which may be either files
 * or parameters.
 *
 * @test   web.unittest.MultipartTest
 * @test   web.unittest.MultipartRequestTest
 * @see    https://tools.ietf.org/html/rfc7578
 */
class Multipart {
  const MIME = 'multipart/form-data';

  private $parts, $params;
  private $peeked= null;

  /**
   * Creates a new Multipart instance
   *
   * @param  iterable $parts
   * @param  [:var] $params
   */
  public function __construct($parts, &$params) {
    if ($parts instanceof \Iterator) {
      $this->parts= $parts;
    } else if ($parts instanceof \IteratorAggregate) {
      $this->parts= $parts->getIterator();
    } else {
      $this->parts= new \ArrayIterator($parts);
    }
    $this->params= &$params;
  }

  /**
   * Returns all parameters *before* files, parsing the request body until
   * that position.
   *
   * @return web.io.Param[]
   */
  public function peek() {
    if (null !== $this->peeked) return $this->peeked;

    $this->peeked= [];
    while ($this->parts->valid()) {
      $part= $this->parts->current();
      if (Part::PARAM !== $part->kind()) break;

      $this->params= $part->append($this->params);
      $this->parts->next();
      $this->peeked[]= $part;
    }
    return $this->peeked;
  }

  /**
   * Returns all parts - files and parameters.
   *
   * @return iterable
   */
  public function parts() {
    if ($this->peeked) foreach ($this->peeked as $param) {
      yield $param->name() => $param;
    }
    $this->peeked= [];

    while ($this->parts->valid()) {
      $name= $this->parts->key();
      $part= $this->parts->current();
      if (Part::PARAM === $part->kind()) {
        $this->params= $part->append($this->params);
      }
      yield $name => $part;
      $this->parts->next();
    }
  }

  /**
   * Returns only parts representing files
   *
   * @return iterable
   */
  public function files() {
    $this->peeked= [];

    while ($this->parts->valid()) {
      $name= $this->parts->key();
      $part= $this->parts->current();
      $kind= $part->kind();
      if (Part::PARAM === $kind) {
        $this->params= $part->append($this->params);
      } else if (Part::FILE === $kind) {
        yield $part->name() => $part;
      }
      $this->parts->next();
    }
  }
}