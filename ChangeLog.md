Web change log
==============

## ?.?.? / ????-??-??

## 4.2.0 / 2024-05-20

* Deprecated the `web.Dispatch` class. This class was never intended to
  be used directly, one would call `web.Request::dispatch()` instead.
  (@thekid)
* Merged PR #112: Refactor dispatching to be handled inside application
  (@thekid)

## 4.1.1 / 2024-04-20

* Fixed request parameters being out of sync with the URI after calling
  the `web.Request::rewrite()` method
  (@thekid)
* Made compatible with `xp-forge/uri` version 3.0.0 - @thekid

## 4.1.0 / 2024-03-24

* Made compatible with XP 12 - @thekid

## 4.0.0 / 2024-01-30

* **Heads up:** Removed deprecated `transfer()` methods from Stream,
  Upload and Response classes, they have been superseded by `transmit()`.
  (@thekid)
* Merged PR #91: Application initialization. Implementations can chose
  to implement the `initialize()` method (*which is empty by default*)
  to perform database migration, wait for dependant services, etcetera,
  when the server starts up.
  (@thekid)
* Merged PR #103: Make it possible to append trace data to the response
  which will appear in the log file
  (@thekid)
* Merged PR #102: Extract static content handling into reusable class.
  See also https://github.com/xp-forge/frontend/pull/39
  (@thekid)
* Merged PR #89: Add optional parameter $append to `cookie()` - @thekid
* Merged PR #107: Fix SAPI uploads with array parameters - @thekid

## 3.12.0 / 2023-12-03

* Merged PR #93: Allow passing or removing environment variables via the
  new `Environment::export()` method
  (@thekid)
* Fixed code not to yield output streams, the AsyncServer API does not
  expect any value there, but it might, see xp-framework/networking#28
  (@thekid)

## 3.11.0 / 2023-12-02

* Ensured the output stream is always closed when it goes out of scope
  (@thekid)
* Removed superfluous layer of output buffering in development webserver
  (@thekid)
* Merged PR #105: Implement `WriteChunks::flush()` to use for explicitely
  flushing
  (@thekid)

## 3.10.0 / 2023-11-20

* Merged PR #104: Make `xp web [name]` load the class `xp.[name].Web`
  (@thekid)
* Allow `-m dev` as a shorthand for `-m develop` following the principle
  "be liberal in what you accept"
  (@thekid)

## 3.9.0 / 2023-11-17

* Improve error messages when class reference given on the command line
  is not a `web.Application` subclass
  (@thekid)
* Added PHP 8.4 to the test matrix - @thekid

## 3.8.1 / 2023-05-22

* Extended EOF handling inside server protocol handler to include NULL,
  preventing warnings inside header reading
  (@thekid)

## 3.8.0 / 2023-05-08

* Merged PR #101: Limit request size (status line and headers) to 16 K.
  This prevents potential out-of-memory scenarios when too many parallel
  requests transmit huge lists of headers slowly. See issue #100 for the
  motivation and how other server implementations handle this
  (@thekid)
* Merged PR #99: Migrate to new testing library - @thekid

## 3.7.0 / 2022-11-19

* Merged PR #98: Catch socket I/O errors and log them in a less-verbose
  manner (*as this is not a server-side issue*). See also #97
  (@thekid)

## 3.6.0 / 2022-11-02

* Merged PR #96: Add ability to check for non-existant properties without
  using exceptions
  (@thekid)

## 3.5.0 / 2022-09-30

* Merged PR #92: File upload transmission. Using `yield from transmit()`
  instead of `transfer()` on file uploads, these can be streamed in an
  asynchronous manner and without blocking other requests.
  (@thekid)

## 3.4.1 / 2022-09-18

* Fixed `web.filters.BehindProxy` stripping query strings - @thekid

## 3.4.0 / 2022-09-18

* Merged PR #95: Allow calling install() from within routing - @thekid
* Merged PR #94: Make `web.Routing` implement `web.Handler` - @thekid

## 3.3.0 / 2022-08-19

* Do not send file contents from `web.handler.FilesFrom` for HTTP *HEAD*
  requests, saving bandwith and processing time.
  (@thekid)

## 3.2.0 / 2022-05-22

* Merged PR #90: URL-encode cookie values. This is in line with what PHP
  and ExpressJS do.
  (@thekid)

## 3.1.0 / 2022-04-24

* Changed `xp web` command to show complete stacktrace for startup errors
  as suggested in issue #88
  (@thekid)
* Fixed reading HTTP requests with headers exceeding 4096 bytes in length
  by using non-blocking mode for reads.
  (@thekid)

## 3.0.2 / 2022-02-25

* Fixed multiple occasions of "Creation of dynamic property" warnings
  raised by PHP 8.2
  (@thekid)

## 3.0.1 / 2021-10-21

* Made library compatible with XP 11 - @thekid

## 3.0.0 / 2021-09-26

* Merged PR #87: Default server profile to "prod" - @thekid
* Merged PR #86: Drop support for XP < 9 - @thekid
* Merged PR #41: Add `web.filters.BehindProxy` filter - @thekid
* Removed deprecated method `web.io.TestOutput::using()` - @thekid
* Merged PR #82, making *async* the default server mode. Other server
  modes are *sequential*, *prefork* and *develop*. See issue #81
  (@thekid)

## 2.13.1 / 2021-09-09

* Merged PR #85: Fixed param value is urlencoded when doing multipart
  requests via SAPI
  (@johannes85, @thekid)

## 2.13.0 / 2021-08-29

* Fixed PHP 8.1 warnings for `IteratorAggregate` interface compatibility
  (@thekid)
* Extended `Routing::cast()` to accept *Application* instances, see #84
  (@thekid)

## 2.12.0 / 2021-06-13

* Changed filters API so that any `web.Filter` implementation can use
  `yield from $invocation->proceed(...)` without having to test whether
  handlers return a generator first, e.g. via `is_iterable()`. Filters
  using `return $invocation->proceed(...)` continue to work. Keep in mind
  they return *before* asynchronous handlers have completely executed!
  (@thekid)

## 2.11.0 / 2021-06-03

* Merged PR #80: Fixed usage of "&" chars in multipart parameters
  (@johannes85)
* Fixed compatibility with older versions of `xp-framework/networking`
  without asynchronous server support. This lead to the server being
  started but not answering any request, see issue #79.
  (@thekid)
* Added support for HTTP status code 103 "Early Hints", see RFC 8297 and
  https://evertpot.com/http/103-early-hints. Note that this does not work
  in the development webserver, see php/php-src#7025.
  (@thekid)

## 2.10.0 / 2021-05-15

* Merged PR #77: Add `Headers::date(int|util.Date)` to return dates in GMT
  according to HTTP spec
  (@thekid)

## 2.9.1 / 2021-04-17

* Fixed issue #75: Development server: Response already flushed - @thekid

## 2.9.0 / 2021-04-10

* Merged PR #74: Add new FilesFrom::with($headers) to add custom headers
  (@thekid)

## 2.8.0 / 2021-04-01

* Merged PR #73: Add new async method `Response::transmit()` replacing
  `Response::transfer()`. While existing code will continue to work, it
  should be rewritten as seen in the pull request!
  (@thekid)
* Merged PR #72: Asynchronous file handling for `web.handler.FilesFrom`.
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