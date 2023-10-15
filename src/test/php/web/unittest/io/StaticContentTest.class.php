<?php namespace web\unittest\io;

use io\{File, TempFile};
use test\{Assert, Before, Test, Values};
use web\io\{StaticContent, TestInput, TestOutput};
use web\{Request, Response, Headers};

class StaticContentTest {
  private $file;

  /**
   * Invokes `serve()` and returns the HTTP response as a string.
   *
   * @param  web.io.StaticContent $content
   * @param  ?io.File $file
   * @param  ?string $mimeType
   * @param  ?web.io.TestInput $input
   * @return string
   */
  private function serve($content, $file, $mimeType= null, $input= null) {
    $res= new Response(new TestOutput());
    $req= new Request($input ?? new TestInput('GET', '/'));

    try {
      foreach ($content->serve($req, $res, $file, $mimeType) ?? [] as $_) { }
    } finally {
      $res->end();
    }

    return preg_replace(
      '/[a-z]{3}, [0-9]{2} [a-z]{3} [0-9]{4} [0-9:]{8} GMT/i',
      '<Date>',
      $res->output()->bytes()
    );
  }

  #[Before]
  public function file() {
    $this->file= (new TempFile(self::class))->containing('Homepage');
    $this->file->move($this->file->getURI().'.html');
  }

  #[Test]
  public function can_create() {
    new StaticContent();
  }

  #[Test]
  public function serve_file() {
    Assert::equals(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 8\r\n".
      "\r\n".
      "Homepage",
      $this->serve(new StaticContent(), $this->file, 'text/html'),
    );
  }

  #[Test]
  public function mime_type_inferred_from_file_extension() {
    Assert::equals(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 8\r\n".
      "\r\n".
      "Homepage",
      $this->serve(new StaticContent(), $this->file),
    );
  }

  #[Test]
  public function head_requests_do_not_include_body() {
    Assert::equals(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 8\r\n".
      "\r\n",
      $this->serve(new StaticContent(), $this->file, null, new TestInput('HEAD', '/')),
    );
  }

  #[Test]
  public function conditional_request() {
    Assert::equals(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 8\r\n".
      "\r\n".
      "Homepage",
      $this->serve(new StaticContent(), $this->file, null, new TestInput('GET', '/', [
        'If-Modified-Since' => Headers::date(time() - 86400)
      ])),
    );
  }

  #[Test]
  public function unmodified_since_date_in_conditional_request() {
    Assert::equals(
      "HTTP/1.1 304 Not Modified\r\n".
      "\r\n",
      $this->serve(new StaticContent(), $this->file, null, new TestInput('GET', '/', [
        'If-Modified-Since' => Headers::date(time() + 86400)
      ])),
    );
  }

  #[Test]
  public function with_headers() {
    $headers= ['Cache-Control' => 'no-cache'];

    Assert::equals(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Cache-Control: no-cache\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 8\r\n".
      "\r\n".
      "Homepage",
      $this->serve((new StaticContent())->with($headers), $this->file),
    );
  }

  #[Test]
  public function with_header_function() {
    $headers= function($uri, $file, $mime) {
      yield 'X-Access-Time' => Headers::date($file->lastAccessed());
    };

    Assert::equals(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "X-Access-Time: <Date>\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 8\r\n".
      "\r\n".
      "Homepage",
      $this->serve((new StaticContent())->with($headers), $this->file),
    );
  }

  #[Test]
  public function serve_non_existant_yields_404() {
    Assert::equals(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 26\r\n".
      "\r\n".
      "The file '/' was not found",
      $this->serve(new StaticContent(), new File('does.not.exist')),
    );
  }

  #[Test]
  public function serve_null_yields_404() {
    Assert::equals(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 35\r\n".
      "\r\n".
      "The file '/not-found' was not found",
      $this->serve(new StaticContent(), null, null, new TestInput('GET', '/not-found')),
    );
  }

  #[Test, Values([['0-3', 'Home'], ['4-7', 'page'], ['0-0', 'H'], ['4-4', 'p'], ['7-7', 'e']])]
  public function range_with_start_and_end($range, $result) {
    Assert::equals(
      "HTTP/1.1 206 Partial Content\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Range: bytes {$range}/8\r\n".
      "Content-Length: ".strlen($result)."\r\n".
      "\r\n".
      $result,
      $this->serve(new StaticContent(), $this->file, null, new TestInput('GET', '/', [
        'Range' => "bytes={$range}" 
      ])),
    );
  }

  #[Test]
  public function range_from_offset_until_end() {
    Assert::equals(
      "HTTP/1.1 206 Partial Content\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Range: bytes 4-7/8\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "page",
      $this->serve(new StaticContent(), $this->file, null, new TestInput('GET', '/', [
        'Range' => 'bytes=4-' 
      ])),
    );
  }

  #[Test, Values([0, 8192, 10000])]
  public function range_last_four_bytes($offset) {
    $padded= (new TempFile(self::class))->containing(str_repeat('*', $offset).'Homepage');

    Assert::equals(
      "HTTP/1.1 206 Partial Content\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Range: bytes ".($offset + 4)."-".($offset + 7)."/".($offset + 8)."\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "page",
      $this->serve(new StaticContent(), $padded, 'text/html', new TestInput('GET', '/', [
        'Range' => 'bytes=-4' 
      ])),
    );
  }

  #[Test]
  public function multiple_ranges() {
    Assert::equals(
      "HTTP/1.1 206 Partial Content\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: multipart/byteranges; boundary=594fa07300f865fe\r\n".
      "Content-Length: 186\r\n".
      "\r\n".
      "\r\n--594fa07300f865fe\r\n".
      "Content-Type: text/html\r\n".
      "Content-Range: bytes 0-3/8\r\n\r\n".
      "Home".
      "\r\n--594fa07300f865fe\r\n".
      "Content-Type: text/html\r\n".
      "Content-Range: bytes 4-7/8\r\n\r\n".
      "page".
      "\r\n--594fa07300f865fe--\r\n",
      $this->serve(new StaticContent(), $this->file, null, new TestInput('GET', '/', [
        'Range' => 'bytes=0-3,4-7' 
      ])),
    );
  }

  #[Test, Values(['bytes=0-2000', 'bytes=4-2000', 'bytes=2000-', 'bytes=2000-2001', 'bytes=2000-0', 'bytes=4-0', 'characters=0-'])]
  public function range_unsatisfiable($range) {
    Assert::equals(
      "HTTP/1.1 416 Range Not Satisfiable\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Range: bytes */8\r\n".
      "\r\n",
      $this->serve(new StaticContent(), $this->file, null, new TestInput('GET', '/', [
        'Range' => $range
      ])),
    );
  }
}