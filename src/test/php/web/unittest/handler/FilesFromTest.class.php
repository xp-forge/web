<?php namespace web\unittest\handler;

use lang\Environment;
use io\Path;
use io\Folder;
use io\File;
use io\FileUtil;
use web\handler\FilesFrom;
use web\Request;
use web\Response;
use web\io\TestInput;
use web\io\TestOutput;

class FilesFromTest extends \unittest\TestCase {
  private $cleanup= [];

  /**
   * Creates files inside a temporary directory and returns its path
   *
   * @param  [:string] $files
   * @return io.Path
   */
  private function pathWith($files) {
    $folder= new Folder(Environment::tempDir(), uniqid($this->name, true));
    $folder->create(0777);
    foreach ($files as $name => $contents) {
      FileUtil::setContents(new File($folder, $name), $contents);
    }
    $this->cleanup[]= $folder;
    return new Path($folder);
  }

  private function assertResponse($expected, $actual) {
    $this->assertEquals($expected, preg_replace(
      '/[a-z]{3}, [0-9]{2} [a-z]{3} [0-9]{4} [0-9:]{8} GMT/i',
      '<Date>',
      $actual
    ));
  }

  /** @return void */
  public function tearDown() {
    foreach ($this->cleanup as $folder) {
      $folder->exists() && $folder->unlink();
    }
  }

  #[@test]
  public function can_create() {
    new FilesFrom(new Path('.'));
  }

  #[@test]
  public function existing_file() {
    $in= new TestInput('GET', '/test.html');
    $out= new TestOutput();

    $files= (new FilesFrom($this->pathWith(['test.html' => 'Test'])));
    $files->handle(new Request($in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Test",
      $out->bytes
    );
  }

  #[@test]
  public function existing_file_unmodified_since() {
    $in= new TestInput('GET', '/test.html', ['If-Modified-Since' => gmdate('D, d M Y H:i:s T', time() + 1)]);
    $out= new TestOutput(); 

    $files= (new FilesFrom($this->pathWith(['test.html' => 'Test'])));
    $files->handle(new Request($in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 304 Not Modified\r\n".
      "\r\n",
      $out->bytes
    );
  }

  #[@test]
  public function index_html() {
    $in= new TestInput('GET', '/');
    $out= new TestOutput(); 

    $files= (new FilesFrom($this->pathWith(['index.html' => 'Home'])));
    $files->handle(new Request($in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Home",
      $out->bytes
    );
  }

  #[@test]
  public function non_existant_file() {
    $in= new TestInput('GET', '/test.html');
    $out= new TestOutput(); 

    $files= (new FilesFrom($this->pathWith([])));
    $files->handle(new Request($in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 35\r\n".
      "\r\n".
      "The file '/test.html' was not found",
      $out->bytes
    );
  }

  #[@test]
  public function non_existant_index_html() {
    $in= new TestInput('GET', '/');
    $out= new TestOutput(); 

    $files= (new FilesFrom($this->pathWith([])));
    $files->handle(new Request($in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 26\r\n".
      "\r\n".
      "The file '/' was not found",
      $out->bytes
    );
  }

  #[@test, @values([
  #  ['0-3', 'Home'],
  #  ['4-7', 'page'],
  #  ['0-0', 'H'],
  #  ['4-4', 'p'],
  #  ['7-7', 'e']
  #])]
  public function range_with_start_and_end($range, $result) {
    $in= new TestInput('GET', '/', ['Range' => 'bytes='.$range]);
    $out= new TestOutput(); 

    $files= (new FilesFrom($this->pathWith(['index.html' => 'Homepage'])));
    $files->handle(new Request($in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 206 Partial Content\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "Content-Type: text/html\r\n".
      "Content-Range: bytes ".$range."/8\r\n".
      "Content-Length: ".strlen($result)."\r\n".
      "\r\n".
      $result,
      $out->bytes
    );
  }

  #[@test]
  public function range_from_offset_until_end() {
    $in= new TestInput('GET', '/', ['Range' => 'bytes=4-']);
    $out= new TestOutput(); 

    $files= (new FilesFrom($this->pathWith(['index.html' => 'Homepage'])));
    $files->handle(new Request($in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 206 Partial Content\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "Content-Type: text/html\r\n".
      "Content-Range: bytes 4-7/8\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "page",
      $out->bytes
    );
  }

  #[@test, @values([0, 8192, 10000])]
  public function range_last_four_bytes($offset) {
    $in= new TestInput('GET', '/', ['Range' => 'bytes=-4']);
    $out= new TestOutput(); 

    $files= (new FilesFrom($this->pathWith(['index.html' => str_repeat('*', $offset).'Homepage'])));
    $files->handle(new Request($in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 206 Partial Content\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "Content-Type: text/html\r\n".
      "Content-Range: bytes ".($offset + 4)."-".($offset + 7)."/".($offset + 8)."\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "page",
      $out->bytes
    );
  }

  #[@test, @values([
  #  'bytes=0-2000',
  #  'bytes=4-2000',
  #  'bytes=2000-',
  #  'bytes=2000-2001',
  #  'bytes=2000-0',
  #  'bytes=4-0',
  #  'characters=0-'
  #])]
  public function range_unsatisfiable($range) {
    $in= new TestInput('GET', '/', ['Range' => $range]);
    $out= new TestOutput();

    $files= (new FilesFrom($this->pathWith(['index.html' => 'Homepage'])));
    $files->handle(new Request($in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 416 Range Not Satisfiable\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "Content-Range: bytes */8\r\n".
      "\r\n",
      $out->bytes
    );
  }

  #[@test]
  public function multi_range() {
    $in= new TestInput('GET', '/', ['Range' => 'bytes=0-3,4-7']);
    $out= new TestOutput(); 

    $files= (new FilesFrom($this->pathWith(['index.html' => 'Homepage'])));
    $files->handle(new Request($in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 206 Partial Content\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
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
      $out->bytes
    );
  }
}