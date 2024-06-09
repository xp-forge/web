<?php namespace xp\web\dev;

use Throwable as Any;
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
    $capture= new CaptureOutput();
    try {
      ob_start();
      yield from $invocation->proceed($req, $res->streaming(function($res, $length) use($capture) {
        return $capture->length($length);
      }));
      $debug= ob_get_clean();
      if (0 === strlen($debug)) return $capture->drain($res);
    } catch (Any $e) {
      $res->answer($e instanceof Error ? $e->status() : 500);
      $debug= ob_get_clean()."\n".Throwable::wrap($e)->toString();
    } finally {
      $capture->end($res);
    }

    $console= sprintf(
      typeof($this)->getClassLoader()->getResource($this->template),
      htmlspecialchars($debug),
      $capture->status,
      htmlspecialchars($capture->message),
      $this->rows($capture->headers),
      htmlspecialchars($capture->bytes)
    );
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