<?php namespace web\handler;

class FrontendResponse extends \web\Response {

  public function __construct($response, $templates, $context) {
    parent::__construct($response);
    $this->templates= $templates;
    $this->context= $context;
  }

  public function entity($entity) {
    $this->send($this->templates->render($this->context['action'], [
      'result'  => $entity,
      'request' => $this->context
    ]));
  }
}