Web change log
==============

## ?.?.? / ????-??-??

* Changed response always to include a `Date` header as per RFC 2616,
  section 14.18. See issue #19
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