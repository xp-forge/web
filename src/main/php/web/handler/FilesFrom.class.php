<?php namespace web\handler;

use io\Path;
use io\File;
use util\MimeType;

class FilesFrom implements \web\Handler {
  private $path;

  /** @param io.Path|io.Folder|string $path */
  public function __construct($path) {
    $this->path= $path instanceof Path ? $path : new Path($path);
  }

  /**
   * Handles a request
   *
   * @param   web.Request $request
   * @param   web.Response $response
   * @return  void
   */
  public function handle($request, $response) {
    $target= new Path($this->path, $request->uri()->path());
    if ($target->isFolder()) {
      $file= new File($target, 'index.html');
    } else {
      $file= $target->asFile();
    }

    $this->serve($request, $response, $file);
  }

  /**
   * Handles a request
   *
   * @param   web.Request $request
   * @param   web.Response $response
   * @param   io.File|io.Path|string $target
   * @return  void
   */
  public function serve($request, $response, $target) {
    $file= $target instanceof File ? $target : new File($target);
    if (!$file->exists()) {
      $response->answer(404, 'Not Found');
      $response->send('The file \''.$request->uri()->path().'\' was not found', 'text/plain');
      return;
    }

    $lastModified= $file->lastModified();
    if ($conditional= $request->header('If-Modified-Since')) {
      if ($lastModified <= strtotime($conditional)) {
        $response->answer(304, 'Not Modified');
        $response->flush();
        return;
      }
    }

    $response->answer(200, 'OK');
    $response->header('Last-Modified', gmdate('D, d M Y H:i:s T', $lastModified));
    $response->transfer($file->in(), MimeType::getByFileName($file->filename), $file->size());
  }
}