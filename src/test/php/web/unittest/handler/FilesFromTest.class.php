<?php namespace web\unittest\handler;

use io\{File, Files, Folder, Path};
use lang\Environment;
use test\{Assert, Test, Values};
use web\handler\FilesFrom;
use web\io\{TestInput, TestOutput};
use web\{Headers, Request, Response};

class FilesFromTest {
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
    static $id= 0;

    $folder= new Folder(Environment::tempDir(), uniqid('test_'.(++$id), true));
    $this->cleanup[]= $this->create($folder, $files);
    return new Path($folder);
  }

  /**
   * Invokes `handle()`
   *
   * @param  web.handler.FilesFrom $files
   * @param  web.Request $req
   * @return string
   */
  private function handle($files, $req) {
    $res= new Response(new TestOutput());
    try {
      foreach ($files->handle($req, $res) ?? [] as $_) { }
    } finally {
      $res->end();
    }

    return preg_replace(
      '/[a-z]{3}, [0-9]{2} [a-z]{3} [0-9]{4} [0-9:]{8} GMT/i',
      '<Date>',
      $res->output()->bytes()
    );
  }

  #[After]
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
    Assert::equals(new Path('.'), (new FilesFrom($arg))->path());
  }

  #[Test]
  public function existing_file() {
    $files= new FilesFrom($this->pathWith(['test.html' => 'Test']));
    Assert::equals(
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
    Assert::equals(
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
    Assert::equals(
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
  public function index_html() {
    $files= new FilesFrom($this->pathWith(['index.html' => 'Home']));
    Assert::equals(
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
    Assert::equals(
      "HTTP/1.1 301 Moved Permanently\r\n".
      "Location: preview/\r\n".
      "\r\n",
      $this->handle($files, new Request(new TestInput('GET', '/preview')))
    );
  }

  #[Test]
  public function non_existant_file() {
    $files= new FilesFrom($this->pathWith([]));
    Assert::equals(
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
    Assert::equals(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 26\r\n".
      "\r\n".
      "The file '/' was not found",
      $this->handle($files, new Request(new TestInput('GET', '/')))
    );
  }

  #[Test, Values(['/./test.html', '/static/../test.html'])]
  public function resolves_paths($uri) {
    $files= new FilesFrom($this->pathWith(['test.html' => 'Test']));
    Assert::equals(
      "HTTP/1.1 200 OK\r\n".
      "Accept-Ranges: bytes\r\n".
      "Last-Modified: <Date>\r\n".
      "X-Content-Type-Options: nosniff\r\n".
      "Content-Type: text/html\r\n".
      "Content-Length: 4\r\n".
      "\r\n".
      "Test",
      $this->handle($files, new Request(new TestInput('GET', $uri)))
    );
  }

  #[Test, Values(['/../credentials', '/static/../../credentials'])]
  public function cannot_access_below_path_root($uri) {
    $files= new FilesFrom(new Folder($this->pathWith(['credentials' => 'secret']), 'webroot'));
    Assert::equals(
      "HTTP/1.1 404 Not Found\r\n".
      "Content-Type: text/plain\r\n".
      "Content-Length: 37\r\n".
      "\r\n".
      "The file '/credentials' was not found",
      $this->handle($files, new Request(new TestInput('GET', $uri)))
    );
  }
}