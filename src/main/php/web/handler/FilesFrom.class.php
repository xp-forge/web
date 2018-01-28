<?php namespace web\handler;

use io\Path;
use io\File;
use util\MimeType;
use web\io\Ranges;

class FilesFrom implements \web\Handler {
  const BOUNDARY = '594fa07300f865fe';

  private $paths;

  /** @param io.Path|io.Folder|string|web.handler.Paths $arg */
  public function __construct($arg) {
    $this->paths= $arg instanceof Paths ? $arg : new Paths($arg);
  }

  /**
   * Handles a request
   *
   * @param   web.Request $request
   * @param   web.Response $response
   * @return  var
   */
  public function handle($request, $response) {
    if (null === ($file= $this->paths->resolve($request->uri()->path()))) {
      $response->answer(404, 'Not Found');
      $response->send('The file \''.$request->uri()->path().'\' was not found', 'text/plain');
      return;
    }

    $this->send($request, $response, $file);
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

    $this->send($request, $response, $file);
  }

  /**
   * Copies a given amount of bytes from the specified file to the output
   *
   * @param  web.io.Output $output
   * @param  io.File $file
   * @param  int $length
   */
  private function copy($output, $file, $length) {
    while ($length && $chunk= $file->read(min(8192, $length))) {
      $output->write($chunk);
      $length-= strlen($chunk);
    }
  }

  /**
   * Sends file
   *
   * @param   web.Request $request
   * @param   web.Response $response
   * @param   io.File $file must've been checked for existance before!
   * @return  void
   */
  private function send($request, $response, $file) {
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
    if (null === ($ranges= Ranges::in($request->header('Range'), $file->size()))) {
      $response->answer(200, 'OK');
      $response->transfer($file->in(), $mimeType, $file->size());
      return;
    }

    if (!$ranges->satisfiable() || 'bytes' !== $ranges->unit()) {
      $response->answer(416, 'Range Not Satisfiable');
      $response->header('Content-Range', 'bytes */'.$ranges->complete());
      $response->flush();
      return;
    }

    $file->open(File::READ);
    $output= $response->output();
    $response->answer(206, 'Partial Content');

    try {
      if ($range= $ranges->single()) {
        $response->header('Content-Type', $mimeType);
        $response->header('Content-Range', $ranges->format($range));
        $response->header('Content-Length', $range->length());

        $file->seek($range->start());
        $response->flush();
        $this->copy($output, $file, $range->length());
      } else {
        $headers= [];
        $trailer= "\r\n--".self::BOUNDARY."--\r\n";

        $length= strlen($trailer);
        foreach ($ranges->sets() as $i => $range) {
          $header= sprintf(
            "\r\n--%s\r\nContent-Type: %s\r\nContent-Range: %s\r\n\r\n",
            self::BOUNDARY,
            $mimeType,
            $ranges->format($range)
          );
          $headers[$i]= $header;
          $length+= strlen($header) + $range->length();
        }

        $response->header('Content-Type', 'multipart/byteranges; boundary='.self::BOUNDARY);
        $response->header('Content-Length', $length);
        $response->flush();
        foreach ($ranges->sets() as $i => $range) {
          $output->write($headers[$i]);
          $file->seek($range->start());
          $this->copy($output, $file, $range->length());
        }
        $output->write($trailer);
      }
    } finally {
      $file->close();
      $output->close();
    }
  }
}