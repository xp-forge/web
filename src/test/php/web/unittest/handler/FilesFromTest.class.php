<?php namespace web\unittest\handler;

use lang\Environment;
use lang\Object;
use io\Path;
use io\Folder;
use io\File;
use io\FileUtil;
use web\handler\FilesFrom;
use web\Request;
use web\Response;

class FilesFromTest extends \unittest\TestCase {
  private $cleanup= [];

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
      $folder->unlink();
    }
  }

  #[@test]
  public function can_create() {
    new FilesFrom(new Path('.'));
  }

  #[@test]
  public function existing_file() {
    $in= new TestInput();
    $out= new TestOutput();

    $files= (new FilesFrom($this->pathWith(['test.html' => 'Test'])));
    $files->handle(new Request('GET', 'http://localhost/test.html', $in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\n".
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
    $in= new TestInput(['If-Modified-Since' => gmdate('D, d M Y H:i:s T', time() + 1)]);
    $out= new TestOutput(); 

    $files= (new FilesFrom($this->pathWith(['test.html' => 'Test'])));
    $files->handle(new Request('GET', 'http://localhost/test.html', $in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 304 Not Modified\r\n".
      "\r\n",
      $out->bytes
    );
  }

  #[@test]
  public function index_html() {
    $in= new TestInput();
    $out= new TestOutput(); 

    $files= (new FilesFrom($this->pathWith(['index.html' => 'Home'])));
    $files->handle(new Request('GET', 'http://localhost/', $in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 200 OK\r\n".
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
    $in= new TestInput();
    $out= new TestOutput(); 

    $files= (new FilesFrom($this->pathWith([])));
    $files->handle(new Request('GET', 'http://localhost/test.html', $in), new Response($out));

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
    $in= new TestInput();
    $out= new TestOutput(); 

    $files= (new FilesFrom($this->pathWith([])));
    $files->handle(new Request('GET', 'http://localhost/', $in), new Response($out));

    $this->assertResponse(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 26\r\n".
      "\r\n".
      "The file '/' was not found",
      $out->bytes
    );
  }
}