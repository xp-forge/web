<?php namespace web;

use web\io\Part;

/**
 * Multipart requests consist of multiple parts, which may be either files
 * or parameters.
 *
 * @test   xp://web.unittest.MultipartRequestTest 
 * @see    https://tools.ietf.org/html/rfc7578
 */
class Multipart {
  const MIME = 'multipart/form-data';

  private $request, $boundary;

  /**
   * Creates a new Multipart instance
   *
   * @param  web.Request $request
   * @param  web.Parameterized $type
   */
  public function __construct($request, $type) {
    $this->request= $request;
    $this->boundary= $type->param('boundary');
  }

  /** @return string */
  public function boundary() { return $this->boundary; }
 
  /**
   * Returns all parts - files and parameters.
   *
   * @return iterable
   */
  public function parts() {
    foreach ($this->request->input()->parts($this->boundary) as $name => $part) {
      if (Part::PARAM === $part->kind()) {
        parse_str($name.'='.$part->value(), $params);
        $this->request->add($params);
      }
      yield $name => $part;
    }
  }

  /**
   * Returns only parts representing files
   *
   * @return iterable
   */
  public function files() {
    foreach ($this->request->input()->parts($this->boundary) as $name => $part) {
      $kind= $part->kind();
      if (Part::PARAM === $kind) {
        parse_str($name.'='.$part->value(), $params);
        $this->request->add($params);
      } else if (Part::FILE === $kind) {
        yield $part->name() => $part;
      }
    }
  }
}