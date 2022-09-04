<?php namespace web;

use io\Path;
use lang\ElementNotFoundException;
use util\{CompositeProperties, FilesystemPropertySource, Objects, PropertySource, ResourcePropertySource};

/**
 * Environment wraps profile, web and document roots as well as configuration
 * and provides accessors for them.
 *
 * @test  xp://web.unittest.EnvironmentTest
 */
class Environment {
  private $profile, $webroot, $docroot, $arguments, $logging;
  private $sources= [];

  /**
   * Creates a new environment
   *
   * @param  string $profile
   * @param  string|io.Path $webroot
   * @param  string|io.Path $docroot
   * @param  (string|util.PropertySource)[] $config
   * @param  string[] $arguments
   * @param  string|string[]|web.Logging $logging Defaults to logging to console
   */
  public function __construct($profile, $webroot= '.', $docroot= '.', $config= [], $arguments= [], $logging= '-') {
    $this->profile= $profile;
    $this->webroot= $webroot instanceof Path ? $webroot : new Path($webroot);
    $this->docroot= $docroot instanceof Path ? $docroot : new Path($docroot);
    foreach ($config as $source) {
      if ($source instanceof PropertySource) {
        $this->sources[]= $source;
      } else if (is_dir($source)) {
        $this->sources[]= new FilesystemPropertySource($source);
      } else if (is_file($source)) {
        $this->sources[]= new FilesystemPropertySource(dirname($source));
      } else if (class_exists(ResourcePropertySource::class)) {
        $this->sources[]= new ResourcePropertySource($source);
      }
    }
    $this->logging= $logging instanceof Logging ? $logging : Logging::of($logging);
    $this->arguments= $arguments;
  }

  /** @return string */
  public function profile() { return $this->profile; }

  /** @return io.Path */
  public function webroot() { return $this->webroot; }

  /** @return io.Path */
  public function docroot() { return $this->docroot; }

  /** @return web.Logging */
  public function logging() { return $this->logging; }

  /** @return io.Path */
  public function tempDir() {
    foreach (['TEMP', 'TMP', 'TMPDIR', 'TEMPDIR'] as $variant) {
      if (isset($_ENV[$variant])) return new Path($_ENV[$variant]);
    }
    return new Path(sys_get_temp_dir());
  }

  /**
   * Returns a path composed of the web root and the given path
   *
   * @param  string|io.Path $path
   * @return io.Path
   */
  public function path($path) {
    return new Path($this->webroot, $path);
  }

  /**
   * Returns a given environment variable
   *
   * @param  string $name
   * @return ?string
   */
  public function variable($name) {
    return false === ($env= getenv($name)) ? null : $env;
  }

  /**
   * Pass a given environment variable and value. Pass NULL in value to
   * remove this environment variable.
   *
   * @param  string $name
   * @param  ?string $value
   * @return self
   */
  public function with($name, $value) {
    if (null === $value) {
      putenv($name);
    } else {
      putenv($name.'='.$value);
    }
    return $this;
  }

  /**
   * Gets properties
   *
   * @param  string $name
   * @return util.PropertyAccess
   * @throws lang.ElementNotFoundException
   */
  public function properties($name) {
    $expand= function($name) { return $this->{$name}(); };
    $found= [];
    foreach ($this->sources as $source) {
      if ($source->provides($name)) {
        $found[]= $source->fetch($name)->expanding('app', $expand);
      }
    }

    switch (sizeof($found)) {
      case 1: return $found[0];
      case 0: throw new ElementNotFoundException(sprintf(
        'Cannot find properties "%s" in any of %s',
        $name,
        Objects::stringOf($this->sources)
      ));
      default: return new CompositeProperties($found);
    }
  }

  /** @return string[] */
  public function arguments() { return $this->arguments; }
}