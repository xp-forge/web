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
   * Serves a single file
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

    $response->header('Accept-Ranges', 'bytes');
    $response->header('Last-Modified', gmdate('D, d M Y H:i:s T', $lastModified));

    $mimeType= MimeType::getByFileName($file->filename);
    if ($range= $request->header('Range')) {
      $size= $file->size();
      $end= $size - 1;
      sscanf($range, 'bytes=%d-%d', $start, $end);

      // Handle "bytes=-4", requesting last four bytes
      if ($start < 0) $start+= $size;

      if ($start >= $size || $end >= $size || $end < $start) {
        $response->answer(416, 'Range Not Satisfiable');
        $response->header('Content-Range', 'bytes */'.$size);
        $response->flush();
        return;
      }

      $response->answer(206, 'Partial Content');
      $response->header('Content-Type', $mimeType);
      $response->header('Content-Range', 'bytes '.$start.'-'.$end.'/'.$size);

      if ($start === $end) {
        $response->header('Content-Length', 0);
        $response->flush();
        return;
      }

      $response->header('Content-Length', $end - $start + 1);
      $file->open(File::READ);
      $file->seek($start);

      $output= $response->output();
      $response->flush();
      try {
        while ($start < $end && ($chunk= $file->read(min(8192, $end - $start + 1)))) {
          $output->write($chunk);
          $start+= strlen($chunk);
        }
      } finally {
        $file->close();
        $output->finish();
      }
    } else {
      $response->answer(200, 'OK');
      $response->transfer($file->in(), $mimeType, $file->size());
    }
  }
}