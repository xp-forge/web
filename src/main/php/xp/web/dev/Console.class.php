<?php namespace xp\web\dev;

use web\{Filter, Response};

/**
 * The development console captures content written via `var_dump()`,
 * `echo` or other builtin output statements and - if any - displays it
 * inside an easily readable format above the real output, using a 200
 * HTTP response status.
 *
 * @see   https://www.php.net/ob_start
 * @test  web.unittest.server.ConsoleTest
 */
class Console implements Filter {
  private $template;

  /** @param string $name */
  public function __construct($name= 'xp/web/dev/console') {
    $this->template= $name.'.html';
  }

  /**
   * Creates HTML table rows
   *
   * @param  [:var] $headers
   * @return string
   */
  private function rows($headers) {
    $r= '';
    foreach ($headers as $name => $value) {
      $r.= '<tr>
        <td class="name">'.htmlspecialchars($name).'</td>
        <td class="value">'.htmlspecialchars(implode(', ', $value)).'</td>
      </tr>';
    }
    return $r;
  }

  /**
   * Filters the request
   *
   * @param  web.Request $req
   * @param  web.Response $res
   * @param  web.filters.Invocation $invocation
   * @return var
   */
  public function filter($req, $res, $invocation) {
    $buffer= new Response(new Buffer());

    try {
      ob_start();
      yield from $invocation->proceed($req, $buffer);
    } finally {
      $buffer->end();
      $debug= ob_get_clean();
    }

    $res->trace= $buffer->trace;
    $out= $buffer->output();
    if (0 === strlen($debug)) {
      $out->drain($res);
    } else {
      $res->status(200, 'Debug');
      $res->send(sprintf(
        typeof($this)->getClassLoader()->getResource($this->template),
        htmlspecialchars($debug),
        $out->status,
        htmlspecialchars($out->message),
        $this->rows($out->headers),
        htmlspecialchars($out->bytes)
      ));
    }
  }
}