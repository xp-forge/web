<?php namespace xp\web;

use web\Application;
use web\handler\FilesIn;

class ServeDocumentRootStatically extends Application {

  public function routes() {
    return ['/' => new FilesIn($this->environment->docroot())];
  }

  /** @return string */
  public function toString() { return nameof($this).'('.$this->environment->docroot().')'; }
}