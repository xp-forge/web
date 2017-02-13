<?php namespace web\handler;

interface Templates {

  /**
   * Renders a template, returning the resulting HTML.
   *
   * @param  string $name
   * @param  var $structure
   * @return string
   */
  public function render($name, $structure);
}