<?php namespace xp\web\dev;

use Closure, Throwable;
use lang\XPException;
use web\{Filter, Response, Error};

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
   * Transforms template
   *
   * @param  string $template
   * @param  [:var] $context
   */
  private function transform($template, $context) {
    return preg_replace_callback(
      '/\{\{([^ }]+) ?([^}]+)?\}\}/',
      function($m) use($context) {
        $value= $context[$m[1]] ?? '';
        return $value instanceof Closure ? $value($context[$m[2]] ?? '') : htmlspecialchars($value);
      },
      $template
    );
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
      $status= 200;
      $kind= 'debug';
      $debug= ob_get_clean();
    } catch (Throwable $t) {
      $buffer->answer($status= $t instanceof Error ? $t->status() : 500);
      $kind= 'error';
      $debug= ob_get_clean()."\n".XPException::wrap($t)->toString();
    } finally {
      $buffer->end();
    }

    $res->trace= $buffer->trace;
    $out= $buffer->output();
    if (0 === strlen($debug)) {
      $out->drain($res);
    } else {
      $res->trace+= ['console' => $kind];
      $res->answer($status, $kind);
      $res->send($this->transform(typeof($this)->getClassLoader()->getResource($this->template), [
        'kind'     => $kind,
        'debug'    => $debug,
        'status'   => $out->status,
        'message'  => $out->message,
        'headers'  => $out->headers,
        'contents' => $out->bytes,
        '#rows'    => function($headers) {
          $r= '';
          foreach ($headers as $name => $value) {
            $r.= '<tr>
              <td class="name">'.htmlspecialchars($name).'</td>
              <td class="value">'.htmlspecialchars(implode(', ', $value)).'</td>
            </tr>';
          }
          return $r;
        }
      ]), 'text/html; charset='.\xp::ENCODING);
    }
  }
}