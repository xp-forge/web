<?php namespace web\handler;

use web\Error;

/**
 * The Response class can be used to control the HTTP status code and headers
 * when it is returned from a delegate
 *
 * ```php
 * #[@post('/resources'), @$element: body]
 * public function addElement($element) {
 *   // TBI: Create element
 *   return Response::created();
 * }
 * ```
 *
 * @test  xp://
 */
class Response {
  private $status;
  private $message;
  private $headers= [];
  private $cookies= [];
  private $error= null;
  public $entity= null;

  /**
   * Creates a new response instance
   *
   * @param  int $status
   * @param  string $message
   */
  private function __construct($status, $message= null) {
    $this->status= $status;
    $this->message= $message;
  }

  /**
   * Creates a new response instance with the status code set to 200 (OK)
   *
   * @return self
   */
  public static function ok() {
    return new self(200);
  }

  /**
   * Creates a new response instance with the status code set to 201 (Created)
   * and an optional location.
   *
   * @param  string $location
   * @return self
   */
  public static function created($location= null) {
    $self= new self(201);
    if (null !== $location) $self->headers['Location']= $location;
    return $self;
  }

  /**
   * Creates a new response instance with the status code set to 204 (No content)
   *
   * @return self
   */
  public static function noContent() {
    return new self(204);
  }

  /**
   * Creates a new response instance with the status code set to 302 (See other)
   * and a specified location.
   *
   * @param  string $location
   * @return self
   */
  public static function see($location) {
    $self= new self(302);
    $self->headers['Location']= $location;
    return $self;
  }

  /**
   * Creates a new response instance with the status code set to 304 (Not modified)
   *
   * @return self
   */
  public static function notModified() {
    return new self(304);
  }

  /**
   * Creates a new response instance with the status code set to 404 (Not found)
   *
   * @param  string $message
   * @return self
   */
  public static function notFound($message= null) {
    return self::error(404, $message);
  }

  /**
   * Creates a new response instance with the status code set to 406 (Not acceptable)
   *
   * @param  string $message
   * @return self
   */
  public static function notAcceptable($message= null) {
    return self::error(406, $message);
  }

  /**
   * Creates a new response instance with the status code optionally set to a given
   * error code (defaulting to 500 - Internal Server Error).
   *
   * @param  int $code
   * @param  string $message
   * @return self
   */
  public static function error($code= 500, $message= null) {
    $self= new self($code);
    $self->error= $message;
    return $self;
  }

  /**
   * Creates a new response instance with the status code set to a given status.
   *
   * @param  int $code
   * @param  string $entity
   * @return self
   */
  public static function status($code, $entity= null) {
    $self= new self($code);
    $self->entity= $entity;
    return $self;
  }

  /**
   * Sets entity
   * 
   * @param  var $entity
   * @return self
   */
  public function entity($entity) {
    $this->entity= $entity;
    return $this;
  }

  /**
   * Adds a header
   * 
   * @param  string $name
   * @param  string $value
   * @return self
   */
  public function header($name, $value) {
    $this->headers[$name]= $value;
    return $this;
  }

  /**
   * Adds a cookie
   * 
   * @param  string $name
   * @param  string $value
   * @return self
   */
  public function cookie($name, $value) {
    $this->cookies[$name]= $value;
    return $this;
  }

  /** @param web.Response */
  public function flush($response) {
    foreach ($this->headers as $name => $value) {
      $response->header($name, $value);
    }
    foreach ($this->cookies as $name => $value) {
      $response->cookie($name, $value);
    }

    if ($this->error) {
      throw new Error($this->status, $this->error);
    } else {
      $response->answer($this->status, $this->message);
      $response->entity($this->entity);
    }
  }
}
