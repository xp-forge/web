<?php namespace web\handler;

class Frontend implements \web\Handler {
  private $actions, $templates;

  /**
   * @param  web.handler.Actions $actions
   * @param  web.handler.Templates $templates
   */
  public function __construct($actions, $templates) {
    $this->actions= $actions;
    $this->templates= $templates;
  }

  /**
   * Handles a request
   *
   * @param   web.Request $request
   * @param   web.Response $response
   * @return  void
   */
  public function handle($request, $response) {
    $action= $this->actions->from($request);
    $action->perform(new FrontendRequest($request), new FrontendResponse($response, $this->templates, [
      'action'  => $action->name(),
      'headers' => $request->headers(),
      'params'  => $request->params(),
      'uri'     => $request->uri()
    ]));
  }
}