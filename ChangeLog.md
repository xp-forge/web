Web change log
==============

## ?.?.? / ????-??-??

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