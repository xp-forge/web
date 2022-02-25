<?php namespace web\unittest\handler;

use io\{File, Files, Folder, Path};
use lang\Environment;
use unittest\{Test, TestCase, Values};
use web\handler\FilesFrom;
use web\io\{TestInput, TestOutput};
use web\{Request, Response, Headers};

class FilesFromTest extends TestCase {
  private $cleanup= [];

  /**
   * Creates files and directories inside a given directory.
   *
   * @param  io.Folder $folder
   * @param  [:var] $files
   * @return io.Folder
   */
  private function create($folder, $files) {
    $folder->create(0777);
    foreach ($files as $name => $contents) {
      if (is_array($contents)) {
        $this->create(new Folder($folder, $name), $contents);
      } else {
        Files::write(new File($folder, $name), $contents);
      }
    }
    return $folder;
  }

  /**
   * Creates files inside a temporary directory and returns its path
   *
   * @param  [:var] $files
   * @return io.Path
   */
  private function pathWith($files) {
    $folder= new Folder(Environment::tempDir(), uniqid($this->name, true));
    $this->cleanup[]= $this->create($folder, $files);
    return new Path($folder);
  }

  /**
   * Assertion helper
   *
   * @param  string $expected
   * @param  web.Response $response
   * @throws unittest.AssertionFailedError
   */
  private function assertResponse($expected, $response) {
    $this->assertEquals($expected, preg_replace(
      '/[a-z]{3}, [0-9]{2} [a-z]{3} [0-9]{4} [0-9:]{8} GMT/i',
      '<Date>',
      $response->output()->bytes()
    ));
  }

  /**
   * Invokes handle()
   *
   * @param  web.handler.FilesFrom $files
   * @param  web.Request $req
   * @return web.Response
   */
  private function handle($files, $req) {
    $res= new Response(new TestOutput());

    try {
      foreach ($files->handle($req, $res) ?? [] as $_) { }
      return $res;
    } finally {
      $res->end();
    }
  }

  /**
   * Invokes serve()
   *
   * @param  web.handler.FilesFrom $files
   * @param  io.File $file
   * @param  ?string $mime
   * @return web.Response
   */
  private function serve($files, $file, $mime= null) {
    $res= new Response(new TestOutput());
    $req= new Request(new TestInput('GET', '/'));

    try {
      foreach ($files->serve($req, $res, $file, $mime) ?? [] as $_) { }
      return $res;
    } finally {
      $res->end();
    }
  }

  /** @return void */
  public function tearDown() {
    foreach ($this->cleanup as $folder) {
      $folder->exists() && $folder->unlink();
    }
  }

  #[Test]
  public function can_create() {
    new FilesFrom(new Path('.'));
  }

  #[Test, Values(eval: '[["."], [new Path(".")]]')]
  public function path($arg) {
    $this->assertEquals(new Path('.'), (new FilesFrom($arg))->path());
  }

  #[Test]
  public function existing_file() {
    $files= new FilesFrom($this->pathWith(['test.html' => 'Test']));
    $this->assertResponse(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Test",
      $this->handle($files, new Request(new TestInput('GET', '/test.html')))
    );
  }

  #[Test]
  public function existing_file_with_headers() {
    $files= (new FilesFrom($this->pathWith(['test.html' => 'Test'])))->with(['Cache-Control' => 'no-cache']);
    $this->assertResponse(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Cache-Control: no-cache\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Test",
      $this->handle($files, new Request(new TestInput('GET', '/test.html')))
    );
  }

  #[Test]
  public function existing_file_with_headers_function() {
    $files= (new FilesFrom($this->pathWith(['test.html' => 'Test'])))->with(function($uri, $file, $mime) {
      if (strstr($file->filename, '.html')) {
        yield 'Cache-Control' => 'no-cache';
      }
    });
    $this->assertResponse(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Cache-Control: no-cache\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Test",
      $this->handle($files, new Request(new TestInput('GET', '/test.html')))
    );
  }

  #[Test]
  public function existing_file_unmodified_since() {
    $files= new FilesFrom($this->pathWith(['test.html' => 'Test']));
    $this->assertResponse(
      "HTTP/1.1 304 Not Modified\r\n".
      "\r\n",
      $this->handle($files, new Request(new TestInput('GET', '/test.html', [
        'If-Modified-Since' => Headers::date(time() + 1)
      ])))
    );
  }

  #[Test]
  public function index_html() {
    $files= new FilesFrom($this->pathWith(['index.html' => 'Home']));
    $this->assertResponse(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Home",
      $this->handle($files, new Request(new TestInput('GET', '/')))
    );
  }

  #[Test]
  public function redirect_if_trailing_slash_missing() {
    $files= new FilesFrom($this->pathWith(['preview' => ['index.html' => 'Home']]));
    $this->assertResponse(
      "HTTP/1.1 301 Moved Permanently\r\n".
      "Location: preview/\r\n".
      "\r\n",
      $this->handle($files, new Request(new TestInput('GET', '/preview')))
    );
  }

  #[Test]
  public function non_existant_file() {
    $files= new FilesFrom($this->pathWith([]));
    $this->assertResponse(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 35\r\n".
      "\r\n".
      "The file '/test.html' was not found",
      $this->handle($files, new Request(new TestInput('GET', '/test.html')))
    );
  }

  #[Test]
  public function non_existant_index_html() {
    $files= new FilesFrom($this->pathWith([]));
    $this->assertResponse(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 26\r\n".
      "\r\n".
      "The file '/' was not found",
      $this->handle($files, new Request(new TestInput('GET', '/')))
    );
  }

  #[Test, Values(['/../credentials', '/static/../../credentials'])]
  public function cannot_access_below_path_root($uri) {
    $files= new FilesFrom(new Folder($this->pathWith(['credentials' => 'secret']), 'webroot'));
    $this->assertResponse(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 37\r\n".
      "\r\n".
      "The file '/credentials' was not found",
      $this->handle($files, new Request(new TestInput('GET', $uri)))
    );
  }

  #[Test, Values([['0-3', 'Home'], ['4-7', 'page'], ['0-0', 'H'], ['4-4', 'p'], ['7-7', 'e']])]
  public function range_with_start_and_end($range, $result) {
    $files= new FilesFrom($this->pathWith(['index.html' => 'Homepage']));
    $this->assertResponse(
      "HTTP/1.1 206 Partial Content\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Range: bytes ".$range."/8\r\n".
      "Content-Length: ".strlen($result)."\r\n".
      "\r\n".
      $result,
      $this->handle($files, new Request(new TestInput('GET', '/', ['Range' => 'bytes='.$range])))
    );
  }

  #[Test]
  public function range_from_offset_until_end() {
    $files= new FilesFrom($this->pathWith(['index.html' => 'Homepage']));
    $this->assertResponse(
      "HTTP/1.1 206 Partial Content\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Range: bytes 4-7/8\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "page",
      $this->handle($files, new Request(new TestInput('GET', '/', ['Range' => 'bytes=4-'])))
    );
  }

  #[Test, Values([0, 8192, 10000])]
  public function range_last_four_bytes($offset) {
    $files= new FilesFrom($this->pathWith(['index.html' => str_repeat('*', $offset).'Homepage']));
    $this->assertResponse(
      "HTTP/1.1 206 Partial Content\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Range: bytes ".($offset + 4)."-".($offset + 7)."/".($offset + 8)."\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "page",
      $this->handle($files, new Request(new TestInput('GET', '/', ['Range' => 'bytes=-4'])))
    );
  }

  #[Test, Values(['bytes=0-2000', 'bytes=4-2000', 'bytes=2000-', 'bytes=2000-2001', 'bytes=2000-0', 'bytes=4-0', 'characters=0-'])]
  public function range_unsatisfiable($range) {
    $files= new FilesFrom($this->pathWith(['index.html' => 'Homepage']));
    $this->assertResponse(
      "HTTP/1.1 416 Range Not Satisfiable\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Range: bytes */8\r\n".
      "\r\n",
      $this->handle($files, new Request(new TestInput('GET', '/', ['Range' => $range])))
    );
  }

  #[Test]
  public function multi_range() {
    $files= new FilesFrom($this->pathWith(['index.html' => 'Homepage']));
    $this->assertResponse(
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
      $this->handle($files, new Request(new TestInput('GET', '/', ['Range' => 'bytes=0-3,4-7'])))
    );
  }

  #[Test]
  public function call_serve_directly() {
    $files= new FilesFrom('.');
    $file= new File($this->pathWith(['test.html' => 'Test']), 'test.html');
    $this->assertResponse(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Test",
      $this->serve($files, $file)
    );
  }

  #[Test]
  public function call_serve_with_non_existant_file() {
    $files= new FilesFrom('.');
    $file= new File($this->pathWith([]), 'test.html');
    $this->assertResponse(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 26\r\n".
      "\r\n".
      "The file '/' was not found",
      $this->serve($files, $file)
    );
  }

  #[Test]
  public function call_serve_without_file() {
    $files= new FilesFrom('.');
    $this->assertResponse(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 26\r\n".
      "\r\n".
      "The file '/' was not found",
      $this->serve($files, null)
    );
  }

  #[Test]
  public function overrride_mime_type_when_invoking_serve() {
    $files= new FilesFrom('.');
    $file= new File($this->pathWith(['test.html' => 'Test']), 'test.html');
    $this->assertResponse(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html; charset=utf-8\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Test",
      $this->serve($files, $file, 'text/html; charset=utf-8')
    );
  }
}