<?php namespace web\io;

use io\File;
use util\MimeType;
use web\Headers;

/**
 * Serves static content by streaming given files. Handles HEAD,
 * conditional and byte range requests. Suppresses content type
 * detection by adding `X-Content-Type-Options: nosniff`.
 *
 * ```php
 * $content= (new StaticContent())->with(['Cache-Control' => '...']);
 * return $content->serve($req, $res, new File('...'), 'image/gif');
 * ```
 *
 * @test web.unittest.io.StaticContentTest
 * @see  web.handler.FilesFrom
 * @see  https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options
 */
class StaticContent {
  const BOUNDARY  = '594fa07300f865fe';
  const CHUNKSIZE = 8192;

  private $headers= [];

  /**
   * Adds headers to successful responses, either from an array or a function.
   *
   * @param  [:string]|function(util.URI, io.File, string): iterable $headers
   * @return self
   */
  public function with($headers) {
    $this->headers= $headers;
    return $this;
  }

  /**
   * Copies a given amount of bytes from the specified file to the output
   *
   * @param  web.io.Output $out
   * @param  io.File $file
   * @param  web.io.Range $range
   * @return iterable
   */
  private function copy($out, $file, $range) {
    $file->seek($range->start());

    $length= $range->length();
    while ($length && $chunk= $file->read(min(self::CHUNKSIZE, $length))) {
      yield 'write' => $out;
      $out->write($chunk);
      $length-= strlen($chunk);
    }
  }

  /**
   * Serves a single file. If no mime type is given, it is detected from
   * the given file's extension.
   *
   * @param   web.Request $request
   * @param   web.Response $response
   * @param   ?io.File|io.Path|string $target
   * @param   ?string $mimeType
   * @return  iterable
   */
  public function serve($request, $response, $target, $mimeType= null) {
    if (null === $target || ($file= $target instanceof File ? $target : new File($target)) && !$file->exists()) {
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

    $mimeType ?? $mimeType= MimeType::getByFileName($file->filename);
    $response->header('Accept-Ranges', 'bytes');
    $response->header('Last-Modified', Headers::date($lastModified));
    $response->header('X-Content-Type-Options', 'nosniff');
    $headers= is_callable($this->headers) ? ($this->headers)($request->uri(), $target, $mimeType) : $this->headers;
    foreach ($headers as $name => $value) {
      $response->header($name, $value);
    }

    if (null === ($ranges= Ranges::in($request->header('Range'), $file->size()))) {
      $response->answer(200, 'OK');
      $response->header('Content-Type', $mimeType);

      if ('HEAD' === $request->method()) {
        $response->header('Content-Length', $file->size());
        $response->flush();
      } else {
        $out= $response->stream($file->size());
        $file->open(File::READ);
        try {
          do {
            yield 'write' => $out;
            $out->write($file->read(self::CHUNKSIZE));
          } while (!$file->eof());
        } finally {
          $file->close();
          $out->close();
        }
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
      $file->isOpen() && $file->close();
      $out->close();
    }
  }
}