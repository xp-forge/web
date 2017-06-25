<?php namespace xp\web;

use web\Application;
use web\handler\FilesFrom;

class ServeDocumentRootStatically extends Application {

  /** @return web.Routing|[:var] */
  public function routes() {
    return ['/' => new FilesFrom($this->environment->docroot())];
  }
}