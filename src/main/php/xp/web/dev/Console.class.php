<?php namespace xp\web\dev;

use web\Filter;
use web\Response;

/**
 * The development console captures content written via `var_dump()`,
 * `echo` or other builtin output statements and - if any - displays it
 * inside an easily readable format above the real output, using a 200
 * HTTP response status.
 *
 * @see   php://ob_start
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
    $buffer= new Buffer();

    try {
      ob_start();
      $result= $invocation->proceed($req, new Response($buffer));
    } finally {
      $debug= ob_get_clean();
      ob_end_clean();
    }

    if (empty($debug)) {
      $buffer->drain($res->output());
    } else {
      $res->status(200, 'Debug');
      $res->send(sprintf(
        typeof($this)->getClassLoader()->getResource($this->template),
        htmlspecialchars($debug),
        $buffer->status,
        htmlspecialchars($buffer->message),
        $this->rows($buffer->headers),
        htmlspecialchars($buffer->bytes)
      ));
    }

    return $result;
  }
}