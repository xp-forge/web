Web change log
==============

## ?.?.? / ????-??-??

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