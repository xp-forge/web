<?php namespace web\handler;

use lang\reflect\Method;
use lang\IllegalArgumentException;
use lang\XPClass;
use io\streams\Streams;
use io\streams\InputStream;

class Delegate implements Action {
  private static $INPUTSTREAM;
  private static $FROM;

  private $instance, $method;
  private $source= [];

  static function __static() {
    self::$INPUTSTREAM= new XPClass(InputStream::class);
    self::$FROM= [
      'value'   => function($req, $name, $default= null) { return $req->value($name, $default); },
      'param'   => function($req, $name, $default= null) { return $req->param($name, $default); },
      'header'  => function($req, $name, $default= null) { return $req->header($name, $default); },
      'stream'  => function($req, $name, $default= null) { return $req->stream() ?: $default; },
      'body'    => function($req, $name, $default= null) {
        $s= $req->stream();
        return $s ? Streams::readAll($s) : $default;
      },
      'default' => function($req, $name, ...$default) {
        if (null !== ($v= $req->value($name))) {
          return $v;
        } else if (null !== ($v= $req->param($name))) {
          return $v;
        } else if (null !== ($v= $req->header($name))) {
          return $v;
        } else if ($default) {
          return $default[0];
        } else {
          throw new IllegalArgumentException('Missing argument $'.$name);
        }
      }
    ];
  }

  /**
   * Returns name and source
   *
   * @param  lang.reflect.Parameter $param
   * @return var[]
   */
  private function source($param) {
    foreach ($param->getAnnotations() as $source => $name) {
      if (isset(self::$FROM[$source])) {
        return [$name ?: $param->getName(), self::$FROM[$source]];
      }
    }

    $stream= self::$INPUTSTREAM->isAssignableFrom($param->getType());
    return [$param->getName(), self::$FROM[$stream ? 'stream' : 'default']];
  }

  public function __construct($instance, Method $method, $matches= []) {
    $this->instance= $instance;
    $this->method= $method;

    foreach ($this->method->getParameters() as $param) {
      if (isset($matches[$param->getName()])) {
        $this->source[$param->getName()]= function($req, $name) use($matches) { return $matches[$name]; };
      } else {
        list($name, $source)= $this->source($param);
        if ($param->isOptional()) {
          $this->source[$name]= function($req, $name) use($source, $param) {
            return $source($req, $name, $param->getDefaultValue());
          };
        } else {
          $this->source[$name]= $source;
        }
      }
    }
  }

  /** @return string */
  public function name() { return $this->method->getName(); }

  /**
   * Performs this action and returns a structure
   *
   * @param   web.Request $request
   * @param   web.Response $response
   * @return  var
   */
  public function perform($request, $response) {
    $args= [];
    foreach ($this->source as $name => $from) {
      $args[]= $from($request, $name);
    }
    return $this->method->invoke($this->instance, $args);
  }
}