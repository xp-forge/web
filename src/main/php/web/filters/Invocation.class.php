<?php namespace web\filters;

class Invocation {

  public function __construct($routing, $filters) {
    $this->routing= $routing;
    $this->filters= $filters;
    $this->offset= 0;
    $this->length= sizeof($filters);
  }

  public function proceed($request, $response) {
    if ($this->offset < $this->length) {
      $this->filters[$this->offset++]->filter($request, $response, $this);
    } else {
      $this->routing->service($request, $response);
    }
  }
}