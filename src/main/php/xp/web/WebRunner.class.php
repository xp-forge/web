<?php namespace xp\web;

use web\Environment;
use web\Request;
use web\Response;
use web\Error;
use web\Status;
use web\InternalServerError;
use lang\ClassLoader;

/**
 * Entry point for web-main.php
 */
class WebRunner {

  /**
   * Logs a request
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  string $message
   * @return void
   */
  private static function log($request, $response, $message= null) {
    $query= $request->uri()->query();
    fprintf(STDERR,
      "  \e[33m[%s %d %.3fkB]\e[0m %d %s %s %s\n",
      date('Y-m-d H:i:s'),
      getmypid(),
      memory_get_usage() / 1024,
      $response->status(),
      $request->method(),
      $request->uri()->path().($query ? '?'.$query : ''),
      $message
    );
  }

  /**
   * Sends an error
   *
   * @param  web.Request $response
   * @param  web.Response $response
   * @param  web.Error $error
   * @param  string $profile
   * @return void
   */
  private static function error($request, $response, $error, $profile) {
    $loader= ClassLoader::getDefault();
    $message= Status::message($error->status());

    $response->answer($error->status(), $message);
    foreach (['web/error-'.$profile.'.html', 'web/error.html'] as $variant) {
      if (!$loader->providesResource($variant)) continue;
      $response->send(sprintf(
        $loader->getResource($variant),
        $error->status(),
        htmlspecialchars($message),
        htmlspecialchars($error->getMessage()),
        htmlspecialchars($error->toString())
      ));
      break;
    }
    self::log($request, $response, $error->toString());
  }

  /** @param string[] $args */
  public static function main($args) {
    $env= new Environment($args[2], $args[0], $args[1], explode('PATH_SEPARATOR', getenv('WEB_CONFIG')));
    $application= (new Source(getenv('WEB_SOURCE'), $env))->application();

    $sapi= new SAPI();
    $request= new Request($sapi);
    $response= new Response($sapi);

    try {
      $application->service($request, $response);
      self::log($request, $response);
    } catch (Error $e) {
      self::error($request, $response, $e);
    } catch (\Throwable $e) {   // PHP7
      self::error($request, $response, new InternalServerError($e), $args[2]);
    } catch (\Exception $e) {   // PHP5
      self::error($request, $response, new InternalServerError($e), $args[2]);
    } finally {
      $response->header('Date', gmdate('D, d M Y H:i:s T'));
      $response->flushed() || $response->flush();
    }
  }
}