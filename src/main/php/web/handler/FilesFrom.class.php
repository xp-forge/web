<?php namespace web\handler;

use io\{File, Path};
use web\Handler;
use web\io\StaticContent;

class FilesFrom implements Handler {
  private $path, $content;

  /** @param io.Path|io.Folder|string $path */
  public function __construct($path) {
    $this->path= $path instanceof Path ? $path : new Path($path);
    $this->content= new StaticContent();
  }

  /** @return io.Path */
  public function path() { return $this->path; }

  /**
   * Adds headers to successful responses, either from an array or a function.
   *
   * @param  [:string]|function(util.URI, io.File, string): iterable $headers
   * @return self
   */
  public function with($headers) {
    $this->content->with($headers);
    return $this;
  }

  /**
   * Handles a request
   *
   * @param   web.Request $request
   * @param   web.Response $response
   * @return  var
   */
  public function handle($request, $response) {
    $path= $request->uri()->path();

    $target= new Path($this->path, $path);
    if ($target->isFolder()) {

      // Add trailing "/" to paths. Users might type directory names without
      // it, leading to resources loaded relatively from within the index.html
      // file to produce wrong absolute URIs. Use _relative_ redirects so this
      // will work without configuration even when paths prefixes are stripped
      // by a reverse proxy!
      if ('/' !== substr($path, -1)) {
        $response->answer(301, 'Moved Permanently');
        $response->header('Location', basename($path).'/');
        $response->flush();
        return;
      }

      $file= new File($target, 'index.html');
    } else {
      $file= $target->asFile();
    }

    return $this->content->serve($request, $response, $file);
  }
}