<?php namespace web\handler;

use io\{File, Path};
use util\MimeType;
use web\Handler;
use web\io\Ranges;

class FilesFrom implements Handler {
  const BOUNDARY = '594fa07300f865fe';

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

    return $this->serve($request, $response, $file);
  }

  /**
   * Copies a given amount of bytes from the specified file to the output
   *
   * @param  web.io.Output $output
   * @param  io.File $file
   * @param  web.io.Range $range
   * @return iterable
   */
  private function copy($output, $file, $range) {
    $file->seek($range->start());

    $length= $range->length();
    while ($length && $chunk= $file->read(min(8192, $length))) {
      $output->write($chunk);
      $length-= strlen($chunk);
      yield;
    }
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
    $response->header('X-Content-Type-Options', 'nosniff');

    $mimeType= MimeType::getByFileName($file->filename);
    if (null === ($ranges= Ranges::in($request->header('Range'), $file->size()))) {
      $response->answer(200, 'OK');
      $response->header('Content-Type', $mimeType);

      $out= $response->stream($file->size());
      $file->open(File::READ);
      try {
        do {
          $out->write($file->read());
          yield;
        } while (!$file->eof());
      } finally {
        $file->close();
        $out->close();
      }
      return;
    }

    if (!$ranges->satisfiable() || 'bytes' !== $ranges->unit()) {
      $response->answer(416, 'Range Not Satisfiable');
      $response->header('Content-Range', 'bytes */'.$ranges->complete());
      $response->flush();
      return;
    }

    $file->open(File::READ);
    $response->answer(206, 'Partial Content');

    try {
      if ($range= $ranges->single()) {
        $response->header('Content-Type', $mimeType);
        $response->header('Content-Range', $ranges->format($range));

        $out= $response->stream($range->length());
        yield from $this->copy($out, $file, $range);
      } else {
        $headers= [];
        $trailer= "\r\n--".self::BOUNDARY."--\r\n";
        $length= strlen($trailer);

        foreach ($ranges->sets() as $i => $range) {
          $headers[$i]= $header= sprintf(
            "\r\n--%s\r\nContent-Type: %s\r\nContent-Range: %s\r\n\r\n",
            self::BOUNDARY,
            $mimeType,
            $ranges->format($range)
          );
          $length+= strlen($header) + $range->length();
        }
        $response->header('Content-Type', 'multipart/byteranges; boundary='.self::BOUNDARY);

        $out= $response->stream($length);
        foreach ($ranges->sets() as $i => $range) {
          $out->write($headers[$i]);
          yield from $this->copy($out, $file, $range);
        }
        $out->write($trailer);
      }
    } finally {
      $file->close();
      $out->close();
    }
  }
}