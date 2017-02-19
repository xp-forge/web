<?php namespace xp\web;

use web\Application;
use web\handler\FilesIn;

class ServeDocumentRootStatically extends Application {

  /** @return web.Routing|[:var] */
  public function routes() {
    return ['/' => new FilesIn($this->environment->docroot())];
  }
}