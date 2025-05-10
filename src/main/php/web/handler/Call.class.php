<?php namespace web\handler;

use lang\FunctionType;

/**
 * Calls to a given invokeable 
 *
 * @test  web.unittest.handler.CallTest
 */
class Call implements \web\Handler {
  private static $TYPE;
  private $invokeable;

  static function __static() {
    self::$TYPE= FunctionType::forName('function(web.Request, web.Response): var');
  }

  /**
   * Creates a new call
   *
   * @param  function(web.Request, web.Response): var $invokeable
   * @throws lang.IllegalArgumentException
   */
  public function __construct($invokeable) {
    $this->invokeable= self::$TYPE->newInstance($invokeable);
  }

  /**
   * Handles a request
   *
   * @param   web.Request $request
   * @param   web.Response $response
   * @return  var
   */
  public function handle($request, $response) {
    return $this->invokeable->__invoke($request, $response);
  }
}