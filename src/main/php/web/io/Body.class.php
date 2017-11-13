<?php namespace web\io;

class Body {
  private $content, $type;

  public function __construct($content, $type) {
    $this->content= $content;
    $this->type= $type;
  }

  /** @return string */
  public function content() { return $this->content; }

  /** @return string */
  public function type() { return $this->type; }

}