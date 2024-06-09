<?php namespace xp\web\dev;

use Closure, Throwable as Any;
use lang\Throwable;
use web\{Error, Filter};

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
   * @return string
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
    $capture= new CaptureOutput();
    try {
      ob_start();
      yield from $invocation->proceed($req, $res->streaming(function($res, $length) use($capture) {
        return $capture->length($length);
      }));

      $kind= 'Debug';
      $debug= ob_get_clean();
      if (0 === strlen($debug)) return $capture->drain($res);
    } catch (Any $e) {
      $kind= 'Error';
      $res->answer($e instanceof Error ? $e->status() : 500);
      $debug= ob_get_clean()."\n".Throwable::wrap($e)->toString();
    } finally {
      $capture->end($res);
    }

    $console= $this->transform(typeof($this)->getClassLoader()->getResource($this->template), [
      'kind'     => $kind,
      'debug'    => $debug,
      'status'   => $capture->status,
      'message'  => $capture->message,
      'headers'  => $capture->headers,
      'contents' => $capture->bytes,
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
    ]);
    $target= $res->output()->stream(strlen($console));
    try {
      $target->begin(200, 'Debug', [
        'Content-Type'  => ['text/html; charset='.\xp::ENCODING],
        'Cache-Control' => ['no-cache, no-store'],
      ]);
      $target->write($console);
    } finally {
      $target->close();
    }
  }
}