<?php namespace web\unittest\handler;

use io\{File, FileUtil, Folder, Path};
use lang\Environment;
use unittest\{Test, Values};
use web\handler\FilesFrom;
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class FilesFromTest extends \unittest\TestCase {
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
        FileUtil::setContents(new File($folder, $name), $contents);
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

  #[Test]
  public function existing_file() {
    $req= new Request(new TestInput('GET', '/test.html'));
    $res= new Response(new TestOutput());

    $files= (new FilesFrom($this->pathWith(['test.html' => 'Test'])));
    $files->handle($req, $res);

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Test",
      $res
    );
  }

  #[Test]
  public function existing_file_unmodified_since() {
    $req= new Request(new TestInput('GET', '/test.html', ['If-Modified-Since' => gmdate('D, d M Y H:i:s T', time() + 1)]));
    $res= new Response(new TestOutput()); 

    $files= (new FilesFrom($this->pathWith(['test.html' => 'Test'])));
    $files->handle($req, $res);

    $this->assertResponse(
      "HTTP/1.1 304 Not Modified\r\n".
      "\r\n",
      $res
    );
  }

  #[Test]
  public function index_html() {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $files= (new FilesFrom($this->pathWith(['index.html' => 'Home'])));
    $files->handle($req, $res);

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Home",
      $res
    );
  }

  #[Test]
  public function redirect_if_trailing_slash_missing() {
    $req= new Request(new TestInput('GET', '/preview'));
    $res= new Response(new TestOutput());

    $files= (new FilesFrom($this->pathWith(['preview' => ['index.html' => 'Home']])));
    $files->handle($req, $res);

    $this->assertResponse(
      "HTTP/1.1 301 Moved Permanently\r\n".
      "Location: preview/\r\n".
      "\r\n",
      $res
    );
  }

  #[Test]
  public function non_existant_file() {
    $req= new Request(new TestInput('GET', '/test.html'));
    $res= new Response(new TestOutput());

    $files= (new FilesFrom($this->pathWith([])));
    $files->handle($req, $res);

    $this->assertResponse(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 35\r\n".
      "\r\n".
      "The file '/test.html' was not found",
      $res
    );
  }

  #[Test]
  public function non_existant_index_html() {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());

    $files= (new FilesFrom($this->pathWith([])));
    $files->handle($req, $res);

    $this->assertResponse(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 26\r\n".
      "\r\n".
      "The file '/' was not found",
      $res
    );
  }

  #[Test, Values(['/../credentials', '/static/../../credentials'])]
  public function cannot_access_below_path_root($uri) {
    $req= new Request(new TestInput('GET', $uri));
    $res= new Response(new TestOutput());

    $path= $this->pathWith(['credentials' => 'secret']);
    $files= new FilesFrom(new Folder($path, 'webroot'));
    $files->handle($req, $res);

    $this->assertResponse(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 37\r\n".
      "\r\n".
      "The file '/credentials' was not found",
      $res
    );
  }

  #[Test, Values([['0-3', 'Home'], ['4-7', 'page'], ['0-0', 'H'], ['4-4', 'p'], ['7-7', 'e']])]
  public function range_with_start_and_end($range, $result) {
    $req= new Request(new TestInput('GET', '/', ['Range' => 'bytes='.$range]));
    $res= new Response(new TestOutput());

    $files= (new FilesFrom($this->pathWith(['index.html' => 'Homepage'])));
    $files->handle($req, $res);

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
      $res
    );
  }

  #[Test]
  public function range_from_offset_until_end() {
    $req= new Request(new TestInput('GET', '/', ['Range' => 'bytes=4-']));
    $res= new Response(new TestOutput());

    $files= (new FilesFrom($this->pathWith(['index.html' => 'Homepage'])));
    $files->handle($req, $res);

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
      $res
    );
  }

  #[Test, Values([0, 8192, 10000])]
  public function range_last_four_bytes($offset) {
    $req= new Request(new TestInput('GET', '/', ['Range' => 'bytes=-4']));
    $res= new Response(new TestOutput());

    $files= (new FilesFrom($this->pathWith(['index.html' => str_repeat('*', $offset).'Homepage'])));
    $files->handle($req, $res);

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
      $res
    );
  }

  #[Test, Values(['bytes=0-2000', 'bytes=4-2000', 'bytes=2000-', 'bytes=2000-2001', 'bytes=2000-0', 'bytes=4-0', 'characters=0-'])]
  public function range_unsatisfiable($range) {
    $req= new Request(new TestInput('GET', '/', ['Range' => $range]));
    $res= new Response(new TestOutput());

    $files= (new FilesFrom($this->pathWith(['index.html' => 'Homepage'])));
    $files->handle($req, $res);

    $this->assertResponse(
      "HTTP/1.1 416 Range Not Satisfiable\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Range: bytes */8\r\n".
      "\r\n",
      $res
    );
  }

  #[Test]
  public function multi_range() {
    $req= new Request(new TestInput('GET', '/', ['Range' => 'bytes=0-3,4-7']));
    $res= new Response(new TestOutput());

    $files= (new FilesFrom($this->pathWith(['index.html' => 'Homepage'])));
    $files->handle($req, $res);

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
      $res
    );
  }
}