Web applications for the XP Framework
========================================================================

[![Build status on GitHub](https://github.com/xp-forge/web/workflows/Tests/badge.svg)](https://github.com/xp-forge/web/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/web/version.svg)](https://packagist.org/packages/xp-forge/web)

Low-level functionality for serving HTTP requests, including the `xp web` runner.

Example
-------

```php
use web\Application;

class Service extends Application {

  public function routes() {
    return [
      '/hello' => function($req, $res) {
        $res->answer(200, 'OK');
        $res->send('Hello '.$req->param('name', 'Guest'), 'text/plain');
      }
    ];
  }
}
```

Run it using:

```bash
$ xp -supervise web Service
@xp.web.srv.Standalone(HTTP @ peer.ServerSocket(Resource id #61 -> tcp://127.0.0.1:8080))
# ...
```

Supports a development webserver which is slower but allows an easy edit/save/reload development process. It uses the [PHP development server](http://php.net/features.commandline.webserver) in the background.

```bash
$ xp -supervise web -m develop Service
@xp.web.srv.Develop(HTTP @ `php -S 127.0.0.1:8080 -t  /home/example/devel/shorturl`)
# ...
```

Now open the website at http://localhost:8080/hello

Server models
-------------
The four server models (*selectable via `-m <model>` on the command line*) are:

* **async** (*the default since 3.0.0*): A single-threaded web server. Handlers can yield control back to the server to serve other clients during lengthy operations such as file up- and downloads.
* **sequential**: Same as above, but blocks until one client's HTTP request handler has finished executing before serving the next request.
* **prefork**: Much like Apache, forks a given number of children to handle HTTP requests. Requires the `pcntl` extension.
* **develop**: As mentioned above, built ontop of the PHP development wenserver. Application code is recompiled and application setup performed from scratch on every request, errors and debug output are handled by the [development console](https://github.com/xp-forge/web/pull/35).

Request and response
--------------------
The `web.Request` class provides the following basic functionality:

```php
use web\Request;

$request= ...

$request->method();       // The HTTP method, e.g. "GET"
$request->uri();          // The request URI, a util.URI instance

$request->headers();      // All request headers as a map
$request->header($name);  // The value of a single header

$request->cookies();      // All cookies
$request->cookie($name);  // The value of a single cookie

$request->params();       // All request parameters as a map
$request->param($name);   // The value of a single parameter
```

The `web.Response` class provides the following basic functionality:

```php
use web\{Response, Cookie};

$response= ...

// Set status code, header(s) and cookie(s)
$response->answer($status);
$response->header($name, $value);
$response->cookie(new Cookie($name, $value));

// Sends body using a given content type
$response->send($body, $type);

// Transfers an input stream using a given content type. Uses
// chunked transfer-encoding.
yield from $response->transmit($in, $type);

// Same as above, but specifies content length before-hand
yield from $response->transmit($in, $type, $size);
```

Both *Request* and *Response* have a `stream()` method for accessing the underlying in- and output streams.

Handlers
--------
A handler (*also referred to as middleware in some frameworks*) is a function which receives a request and response and uses the above functionality to handle communication.

```php
use web\Handler;

$redirect= new class() implements Handler {

  public function handle($req, $res) {
    $req->status(302);
    $req->header('Location', 'https://example.com/');
  }
};
```

This library comes with `web.handler.FilesFrom` - a handler for serving files. It takes care of conditional requests (*with If-Modified-Since*) as well requests for content ranges, and makes use of the asynchronous capabilities if available, see [here](https://github.com/xp-forge/web/pull/72).

Filters
-------
Filters wrap around handlers and can perform tasks before and after the handlers are invoked. You can use the request's `pass()` method to pass values - handlers can access these using `value($name)` / `values()`.

```php
use web\Filter;
use util\profiling\Timer;
use util\log\{Logging, LogCategory};

$timer= new class(Logging::all()->toConsole()) implements Filter {
  private $timer;

  public function __construct(private LogCategory $cat) {
    $this->timer= new Timer();
  }

  public function filter($request, $response, $invocation) {
    $this->timer->start();
    try {
      yield from $invocation->proceed($request, $response);
    } finally {
      $this->cat->debugf('%s: %.3f seconds', $request->uri(), $this->timer->elapsedTime());
    }
  }
}
```

*By using `yield from`, you guarantee asynchronous handlers will have completely executed before the time measurement is run on in the `finally` block.*

File uploads
------------
File uploads are handled by the request's `multipart()` method. In contrast to how PHP works, file uploads are streamed and your handler starts running with the first byte transmitted!

```php
use io\Folder;

$uploads= new Folder('...');
$handler= function($req, $res) use($uploads) {
  if ($multipart= $req->multipart()) {

    // See https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/100
    if ('100-continue' === $req->header('Expect')) {
      $res->hint(100, 'Continue');
    }

    // Transmit files to uploads directory asynchronously
    $files= [];
    $bytes= 0;
    foreach ($multipart->files() as $name => $file) {
      $files[]= $name;
      $bytes+= yield from $file->transmit($uploads);
    }

    // Do something with files and bytes...
  }
};
```

Early hints
-----------
An experimental status code with which headers can be sent to a client early along for it to be able to make optimizations, e.g. preloading scripts and stylesheets.

```php
$handler= function($req, $res) {
  $res->header('Link', [
    '</main.css>; rel=preload; as=style',
    '</script.js>; rel=preload; as=script'
  ]);
  $res->hint(103);

  // Do some processing here to render $html
  $html= ...

  $res->answer(200, 'OK');
  $res->send($html, 'text/html; charset=utf-8');
}
```

See https://evertpot.com/http/103-early-hints

Internal redirects
------------------
On top of external redirects which are triggered by the 3XX status codes, requests can also be redirected internally using the `dispatch()` method. This has the benefit of not requiring clients to perfom an additional request.

```php
use web\Application;

class Site extends Application {

  public function routes() {
    return [
      '/home' => function($req, $res) {
        // Home page
      },
      '/' => function($req, $res) {
        // Routes are re-evaluated as if user had called /home
        return $req->dispatch('/home');
      },
    ];
  }
}
```

Logging
-------
By default, logging goes to standard output and will be visible in the console the `xp web` command was invoked from. It can be influenced via the command line as follows:

* `-l server.log`: Writes to the file server.log, creating it if necessary
* `-l -`: Writes to standard output
* `-l - -l server.log`: Writes to both of the above

More fine-grained control as well as integrating with [the logging library](https://github.com/xp-framework/logging) can be achieved from inside the application, see [here](https://github.com/xp-forge/web/pull/48).

Performance
-----------
Because the code for the web application is only compiled once when using production servers, we achieve lightning-fast request/response roundtrip times:

![Network console screenshot](https://github.com/xp-forge/web/assets/696742/2707a921-8ae2-4884-ae33-59972a8e7a12)

See also
--------
This library provides for the very basic functionality. To create web frontends or REST APIs, have a look at the following libraries built ontop of this:

* [Web frontends](https://github.com/xp-forge/frontend)
* [Sessions](https://github.com/xp-forge/sessions)
* [Authentication](https://github.com/xp-forge/web-auth)
* [REST APIs](https://github.com/xp-forge/rest-api)
* [Run XP web applications on AWS lambda using API Gateway](https://github.com/xp-forge/lambda-ws)
