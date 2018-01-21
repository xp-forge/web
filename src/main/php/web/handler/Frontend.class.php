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
   * @return  var
   */
  public function handle($request, $response) {
    $action= $this->actions->from($request);
    if (null === ($result= $action->perform($request, $response))) return;

    // Render template, passing request as well as the result returned from action
    $name= $action->name();
    $rendered= $this->templates->render($name, [
      'result'  => $result,
      'request' => [
        'action'  => $name,
        'headers' => $request->headers(),
        'params'  => $request->params(),
        'uri'     => $request->uri()
      ]
    ]);

    $response->answer(200, 'OK');
    $response->send($rendered, 'text/html');
  }
}