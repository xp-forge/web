<?php namespace web\filters;

use Closure;
use web\Filter;

/**
 * Cross-Origin Resource Sharing (CORS)
 *
 * @see  https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CORS
 * @test web.unittest.filters.CORSTest
 */
class CORS implements Filter {
  public $origins= '';
  public $methods= [];
  public $headers= [];
  public $expose= [];
  public $maxAge= null;
  public $credentials= false;

  /**
   * Sets the `Access-Control-Allow-Origin` header specifying either a single
   * origin which tells browsers to allow that origin to access the resource;
   * or else — for requests without credentials — the * wildcard tells browsers
   * to allow any origin to access the resource.
   *
   * @param  string|function(string): string $origins
   */
  public function origins($origins): self {
    $this->origins= $origins;
    return $this;
  }

  /**
   * Sets the `Access-Control-Allow-Methods` header, specifying the method or
   * methods allowed when accessing the resource. This is used in response to a
   * preflight request.
   *
   * @param  string|string[] $origins
   */
  public function methods($methods): self {
    $this->methods= is_string($methods) ? preg_split('/, ?/', $methods) : (array)$methods;
    return $this;
  }

  /**
   * Sets the `Access-Control-Allow-Headers` header, used in response to a preflight
   * request to indicate which headers can be used when making the actual request.
   *
   * @param  string|string[] $headers
   */
  public function headers($headers): self {
    $this->headers= is_string($headers) ? preg_split('/, ?/', $headers) : (array)$headers;
    return $this;
  }

  /**
   * Sets the `Access-Control-Max-Age` header, indicating how long the results of
   * a preflight request can be cached. Pass `null` to use the browser's default.
   */
  public function maxAge(?int $seconds): self {
    $this->maxAge= $seconds;
    return $this;
  }

  /**
   * Sets the `Access-Control-Expose-Headers` header, adding the specified headers
   * to the allowlist that JavaScript in browsers is allowed to access.
   *
   * @param  string|string[] $headers
   */
  public function expose($headers): self {
    $this->expose= is_string($headers) ? preg_split('/, ?/', $headers) : (array)$headers;
    return $this;
  }

  /**
   * Sets the `Access-Control-Allow-Credentials` header, indicating whether or not
   * the response to the request can be exposed when the credentials flag is true.
   */
  public function credentials(bool $flag): self {
    $this->credentials= $flag;
    return $this;
  }
  
  public function filter($request, $response, $invocation) {
    $origin= $request->header('Origin');
    if (null !== $origin) {
      $response->header('Vary', 'Origin');
      $response->header('Access-Control-Allow-Origin', $this->origins instanceof Closure
        ? ($this->origins)($origin)
        : $this->origins
      );

      // All requests include expose-headers and credentials
      $this->expose && $response->header('Access-Control-Expose-Headers', implode(', ', $this->expose));
      $this->credentials && $response->header('Access-Control-Allow-Credentials', 'true');

      // Preflight requests also include methods, headers and max-age
      if (null !== $request->header('Access-Control-Request-Method')) {
        $this->methods && $response->header('Access-Control-Allow-Methods', implode(', ', $this->methods));
        $this->headers && $response->header('Access-Control-Allow-Headers', implode(', ', $this->headers));
        $this->maxAge && $response->header('Access-Control-Max-Age', $this->maxAge);
        $response->answer(204);
        return;
      }
    }
    return $invocation->proceed($request, $response);
  }
}