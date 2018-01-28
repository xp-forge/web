<?php namespace web\unittest\handler;

use web\handler\Paths;
use io\File;
use io\FileUtil;
use io\Folder;
use io\Path;
use lang\Environment;
use lang\IllegalStateException;

class PathsTest extends \unittest\TestCase {
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

  /** @return void */
  public function tearDown() {
    foreach ($this->cleanup as $folder) {
      $folder->exists() && $folder->unlink();
    }
  }

  #[@test]
  public function can_create() {
    new Paths();
  }

  #[@test]
  public function can_create_with_path() {
    new Paths($this->pathWith([]));
  }

  #[@test]
  public function resolve_existant_file() {
    $path= $this->pathWith(['test.html' => 'Test']);
    $this->assertEquals(new File($path, 'test.html'), (new Paths($path))->resolve('/test.html'));
  }

  #[@test]
  public function resolve_non_existant_file() {
    $path= $this->pathWith(['test.html' => 'Test']);
    $this->assertNull((new Paths($path))->resolve('/non-existant.html'));
  }

  #[@test]
  public function absent_file_used_for_non_existant_file() {
    $file= new File($this->pathWith(['error404.html' => 'Test']), 'error404.html');
    $this->assertEquals($file, (new Paths())
      ->absent($file)
      ->resolve('/')
    );
  }

  #[@test]
  public function absent_function_used_for_non_existant_file() {
    $file= new File($this->pathWith(['error404.html' => 'Test']), 'error404.html');
    $this->assertEquals($file, (new Paths())
      ->absent(function($uri) use($file) { return $file; })
      ->resolve('/')
    );
  }

  #[@test]
  public function index_html() {
    $path= $this->pathWith(['index.html' => 'Test']);
    $this->assertEquals(new File($path, 'index.html'), (new Paths($path))->resolve('/'));
  }

  #[@test]
  public function index_html_variation() {
    $path= $this->pathWith(['index.htm' => 'Test']);
    $this->assertEquals(
      new File($path, 'index.htm'),
      (new Paths($path))->indexes('index.html', 'index.htm')->resolve('/')
    );
  }

  #[@test]
  public function search_path_used() {
    $paths= [
      $this->pathWith(['a.html' => 'Path 0']),
      $this->pathWith(['b.html' => 'Path 1'])
    ];
    $this->assertEquals(new File($paths[1], 'b.html'), (new Paths(...$paths))->resolve('/b.html'));
  }

  #[@test]
  public function first_occurrence_from_search_path_picked() {
    $paths= [
      $this->pathWith(['b.html' => 'Path 0']),
      $this->pathWith(['b.html' => 'Path 1'])
    ];
    $this->assertEquals(new File($paths[0], 'b.html'), (new Paths(...$paths))->resolve('/b.html'));
  }

  #[@test, @values(['/static', '/static/'])]
  public function strip($prefix) {
    $path= $this->pathWith(['a.html' => 'Test']);
    $this->assertEquals(new File($path, 'a.html'), (new Paths($path))
      ->strip($prefix)
      ->resolve('/static/a.html')
    );
  }

  #[@test, @expect(IllegalStateException::class)]
  public function error_raised_when_prefix_not_in_request() {
    (new Paths())->strip('/static')->resolve('/index.html');
  }
}