Web change log
==============

## ?.?.? / ????-??-??

* Merged PR #72. Asynchronous file handling for `web.handler.FilesFrom`.
  (@thekid)
* Added support for interruptible handlers. These handlers can hand back
  control to the server and allow for further requests to be handled by
  using `yield`. Typical usecases would be file uploads and downloads,
  during which the server would normally be blocked. See issue #70.
  (@thekid)

## 2.7.0 / 2021-03-20

* Set `Server` header to *XP* to be able to distinguish responses
  (@thekid)
* Removed `Host` header, it's a request-only header. See
  https://webhint.io/docs/user-guide/hints/hint-no-disallowed-headers/
  (@thekid)
* Added `X-Content-Type-Options: nosniff` to headers when serving static
  content via `web.handlers.FilesFrom` to prevent UAs from guessing. See
  https://webhint.io/docs/user-guide/hints/hint-x-content-type-options/
  (@thekid)

## 2.6.1 / 2021-03-17

* Fixed server being unresponsive after a file upload was cancelled
  during its transmission
  (@thekid)

## 2.6.0 / 2021-02-13

* Merged PR #68: Add `Environment::path()` utility method - @thekid

## 2.5.1 / 2021-02-13

* Fixed issue #69: Warnings with PHP 8.1 - @thekid

## 2.5.0 / 2021-01-30

* Merged PR #67: Expand web environment attributes inside config files
  (@thekid)

## 2.4.0 / 2020-11-29

* Allowed supplying `--` on the command line to separate subcommand
  arguments from arguments passed to the application class.
  (@thekid)

## 2.3.1 / 2020-10-04

* Rename (internally used) class `web.routing.Match` to *RouteMatch* to
  restore PHP 8 compatibility. PHP 8 defines `match` as a keywords, see
  https://wiki.php.net/rfc/match_expression_v2
  (@thekid)

## 2.3.0 / 2020-09-03

* Merged PR #66: Parse multipart payloads up until the first file
  (@thekid, @johannes85)

## 2.2.0 / 2020-07-12

* Merged PR #64: Add integration tests starting a web server and
  performing roundtrips
  (@thekid)
* Merged PR #63: File uploads. This adds a `multipart()` method to the
  `Request` class from which files can be retrieved. Inside the XP web-
  server, uploads are streamed; inside PHP SAPIs, uploads are stored
  inside a temporary directory prior to processing via userland code.
  (@thekid)
* Added `web.Response::hint()` method to send HTTP/1.1 1XX statuses
  (@thekid)
* Fixed PHP 8.0 compatibility by using `[]` for string offset access
  instead of the removed curly braces syntax.
  (@thekid)
* Changed `web.io.ReadChunks` to defer reading from input stream until
  one of its I/O methods is called.
  (@thekid)
* Merged PR #62: Replace ContentType class with reusable header parser 
  (@thekid)

## 2.1.0 / 2020-06-06

* Included server startup time in `xp web` runner's output - @thekid

## 2.0.0 / 2020-04-10

* Implemented xp-framework/rfc#334: Drop PHP 5.6:
  . **Heads up:** Minimum required PHP version now is PHP 7.0.0
  . Rewrote code base, grouping use statements
  . Converted `newinstance` to anonymous classes
  . Rewrote `isset(X) ? X : default` to `X ?? default`
  (@thekid)

## 1.10.2 / 2020-04-10

* Implemented xp-framework/rfc#335: Remove deprecated key/value pair
  annotation syntax
  (@thekid)

## 1.10.1 / 2019-12-01

* Made compatible with XP 10 - @thekid

## 1.10.0 / 2019-11-03

* Merged PR #61: Also accept filter functions in constructor - @thekid

## 1.9.0 / 2019-08-21

* Added start(), header() and body() accessors to `web.io.TestOutput`
  (@thekid)
* **Heads up:** Deprecated `web.io.TestOutput::using()` in favor of its
  constructor, which now accepts the same arguments.
  (@thekid)
* Merged PR #56: `web.io.TestOutput` constructors - @thekid
* Merged PR #55: Calculate content length of given body - @thekid
* Merged PR #54: Add ability to pass body as map to `web.io.TestInput`
  (@thekid)

## 1.8.0 / 2019-08-19

* Heads up: Always treat first parameter to `Request::dispatch()` as
  absolute path!
  (@thekid)
* Added optional parameter to `Request::dispatch()` to allow passing
  request parameters.
  (@thekid)

## 1.7.0 / 2019-08-16

* Made compatible with PHP 7.4 - don't use `{}` for string offset;
  see https://wiki.php.net/rfc/deprecate_curly_braces_array_access
  (@thekid)

## 1.6.4 / 2018-10-09

* Added a workaround for Apache's FastCGI not being able to handle
  chunked transfer encoding in combination with gzip, see
  https://bz.apache.org/bugzilla/show_bug.cgi?id=53332
  (@johannes85, @thekid)

## 1.6.3 / 2018-10-09

* Fixed "CONTENT_LENGTH" and "CONTENT_TYPE" request meta-variables
  not being honored when behind a CGI interface
  (@thekid)

## 1.6.2 / 2018-10-09

* Fixed `WEB_CONFIG` multiple config path parsing - @thekid
* Fixed `WEB_LOG` to default to no logging in web-main entry point;
  previously, this would try to create a file with an empty name.
  (@thekid)
* Documented environment variables in `xp.web.WebRunner` - @thekid

## 1.6.1 / 2018-08-27

* Fixed `Transfer-Encoding: chunked` for development webserver - @thekid
* Closed issue #26: Handle preconnect (*was already handled*) - @thekid
* Changed protocol to send `400 Bad Request` when neither receiving a
  complete HTTP status line within the initial socket read nor after an
  additional 100 ms.
  (@thekid)

## 1.6.0 / 2018-08-24

* Changed `FilesFrom` handler to support directories without trailing
  slashes; and to add it if necessary be redirecting the user agent.
  Users might type directory names without "/", leading to resources
  loaded relatively from within the index.html file to produce wrong
  absolute URIs. This mirrors Apache's *DirectorySlash* directive, see
  http://httpd.apache.org/docs/2.4/mod/mod_dir.html#directoryslash
  (@thekid)

## 1.5.1 / 2018-08-21

* Fixed console and file loggers when logging errors - @thekid

## 1.5.0 / 2018-08-21

* Merged PR #48: Logging - @thekid

## 1.4.2 / 2018-08-16

* Fixed `Class "com.example.App+xp.web.dev.Console" could not be found`
  errors when using development webserver.
  (@thekid)

## 1.4.1 / 2018-08-14

* Changed `xp web` commandline to allow using filenames as application
  source. See feature request #47
  (@thekid)

## 1.4.0 / 2018-08-12

* Fixed issue #46: Uncaught exceptions from application setup in
  development mode
  (@thekid)
* Merged PR #45: Pass all additional command line arguments to app
  environment
  (@thekid)

## 1.3.0 / 2018-06-07

* Merged PR #42: Response cookies - @mikey179, @thekid

## 1.2.0 / 2018-04-29

* Allowed supplying an array to `matching()`'s first argument, creating
  routes for all of its elements
  (@thekid)
* Fixed `dispatch()` in conjunction with filters / nested routing
  (@thekid)

## 1.1.0 / 2018-04-29

* Merged PR #38: Dispatching - high-performance internal redirects w/o
  the protocol overhead.
  (@thekid)

## 1.0.1 / 2018-04-22

* Fixed SSL and HTTP version detection when running inside PHP SAPIs,
  e.g. Apache; or the development webserver.
  (@thekid)

## 1.0.0 / 2018-04-10

* Fixed handling of HTTP/1.0 requests:
  - Answer with HTTP/1.0 in response status line
  - Close connection unless `Connection: keep-alive` is sent
  - Do not answer with chunked with persistent connections, see
    https://tools.ietf.org/html/rfc2068#section-19.7.1
  (@thekid)

## 0.14.1 / 2018-02-13

* Fixed development webserver not respecting HTTP status codes properly
  (@thekid)

## 0.14.0 / 2018-02-12

* Merged PR #35: Development console. The console is enabled by default
 inside the development webserver and can be activated by prepending the
 `WEB_SOURCE` enviroment variabe with `+xp.web.dev.Console`.
  (@thekid)
* Fixed development webserver shtudown on Un\*x systems - @thekid

## 0.13.0 / 2018-02-05

* Changed chunked transfer encoding to buffer 4k bytes before sending
  a chunk; preventing blowing up the response for repeated small writes.
  See https://gist.github.com/magnetikonline/11312172
  (@thekid)
* Fixed uncaught exceptions when errors occur after starting streaming
  (@thekid)

## 0.12.0 / 2018-01-31

* Fixed "undefined function getallheaders()" when using FPM. According
  to [the documentation](http://php.net/getallheaders), it should exist,
  but reality shows it doesn't. See https://bugs.php.net/bug.php?id=62596
  (@thekid)
* Merged PR #33: Remove frontend handler. This library will cover the
  HTTP basics, while other more advanced usecases should reside in their
  own respective library
  (@thekid)

## 0.11.0 / 2018-01-21

* Fixed issue #32: Canonicalize URL before matching - @thekid
* Changed handler return type from `void` to `var` and ensured anything
  returned from a handler will be returned from routing and filters.
  Implements functionality suggested in #31
  (@thekid)
* Changed `web.filters.Invocation`'s constructor to be more liberal as
  to what it accepts for its routing argument
  (@thekid)

## 0.10.0 / 2018-01-20

* Implemented #30: Request::toString(). The output includes method, URI
  and the HTTP headers sent with the request.
  (@thekid)
* Implemented #29: Exposed "TestInput" and "TestOutput" classes in `web.io`
  package. This way, people wishing to test their filters and handlers can
  easily unittest them.
  (@thekid)

## 0.9.0 / 2017-12-22

* Fixed #28: Send "Content-Length: 0" for empty responses - @thekid
* Merged PR #25: Rewrite - @thekid

## 0.8.3 / 2017-12-05

* Fixed PHP 7.2 compatibility - @thekid

## 0.8.2 / 2017-11-25

* Suppressed PHP CLI server log messages (which are written to STDERR)
  in `-m develop` since we're writing our own logfile.
  (@thekid)

## 0.8.1 / 2017-11-19

* Fixed issue #23: Multiple location headers - @thekid

## 0.8.0 / 2017-11-19

* Merged PR #22: Cookie handling - @thekid
* Merged PR #21: Multiple headers - @thekid
* Defaulted server to keep-alive connections - @thekid

## 0.7.0 / 2017-11-12

* Merged PR #20: Support development webserver (`xp web -m develop`)
  (@thekid)
* Changed response always to include a `Date` header as per RFC 2616,
  section 14.18. See issue #19
  (@thekid)

## 0.6.3 / 2017-08-16

* Prevented possible security problems by escaping error messages
  (@thekid)

## 0.6.2 / 2017-07-07

* Added support for prefork mode; use `-m prefork[,n]` on command line
  (@thekid)

## 0.6.1 / 2017-07-07

* Added `NO_KEEPALIVE` environment variable to switch off keep-alive
  semantics. This might be causing problems with certain proxy setups
  (@thekid)

## 0.6.0 / 2017-07-07

* Changed logging to contain full stack trace of exceptions - @thekid
* Close socket on malformed requests - @thekid

## 0.5.0 / 2017-07-03

* Added `web.Request::stream()` method to read the raw data sent along with
  e.g. POST or PUT requests.
  (@thekid)

## 0.4.0 / 2017-06-30

* Fixed issue #15: curl: (52) Empty reply from server - @thekid
* Implemented issue #12: Simplify streaming to the response
  - PR #14: Add stream() method
  - PR #13: Refactor Output to abstract base class implementing OutputStream
  (@thekid)

## 0.3.0 / 2017-06-25

* Merged pull request #9: Ranges support - @thekid
* Merged pull request #8: Refactor output - @thekid
* Fixed issue #7: Dependencies missing - @thekid
* Fixed issue #6: Chunked transfer encoding - @thekid

## 0.2.0 / 2017-06-04

* Added forward compatibility with XP 9.0.0 - @thekid

## 0.1.0 / 2017-05-07

* Hello World! First release - @thekid