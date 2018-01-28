<?php namespace web\handler;

use io\Path;
use io\Folder;
use io\File;
use lang\IllegalStateException;

/**
 * Paths can be used as constructor argument to the `FilesFrom` class
 * which enables finer-granular control over the request URI to file
 * resolution process.
 *
 * @test  xp://web.unittest.handler.PathsTest
 */
class Paths {
  private $search= [];
  private $strip= '/';
  private $absent= null;
  private $indexes= ['index.html'];
  
  /** @param (io.Path|io.Folder|string)... $paths */
  public function __construct(... $paths) {
    foreach ($paths as $path) {
      if ($path instanceof Path) {
        $this->search[]= $path;
      } else if ($path instanceof Folder) {
        $this->search[]= new Path($path);
      } else {
        $this->search[]= new Path($path);
      }
    }
  }

  /**
   * Sets index documents. Defaults to "index.html"
   *
   * @param  string... $names Filenames without path!
   * @return self
   */
  public function indexes(...$names) {
    $this->indexes= $names;
    return $this;
  }

  /**
   * Sets prefix to strip
   *
   * @param  string $prefix
   * @return self
   */
  public function stripping($prefix) {
    $this->strip= rtrim($prefix, '/').'/';
    return $this;
  }

  /**
   * Sets default file to serve
   *
   * @param  io.File|io.Path|string|function(string): File $arg
   * @return self
   */
  public function absent($arg) {
    if ($arg instanceof \Closure) {
      $this->absent= $arg;
    } else {
      $file= $arg instanceof File ? $arg : new File($arg);
      $this->absent= function($uri) use($file) { return $file; };
    }
    return $this;
  }

  /**
   * Resolves a URI
   *
   * @param  string $uri
   * @return io.File $file
   */
  public function resolve($uri) {
    $prefix= strlen($this->strip);
    if (0 !== strncmp($uri, $this->strip, $prefix)) {
      throw new IllegalStateException('URI '.$uri.' does not contain prefix '.$this->strip);
    }

    $path= substr($uri, $prefix);
    foreach ($this->search as $search) {
      $target= new Path($search, $path);
      if ($target->isFolder()) {
        foreach ($this->indexes as $index) {
          $file= new File($target, $index);
          if ($file->exists()) return $file;
        }
      } else {
        $file= $target->asFile();
        if ($file->exists()) return $file;
      }
    }

    return null === $this->absent ? null : $this->absent->__invoke($uri);
  }
}