Web applications for the XP Framework
========================================================================

[![Build status on GitHub](https://github.com/xp-forge/web/workflows/Tests/badge.svg)](https://github.com/xp-forge/web/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/web/version.png)](https://packagist.org/packages/xp-forge/web)

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
@xp.web.Serve(HTTP @ peer.ServerSocket(resource(type= Socket, id= 88) -> tcp://127.0.0.1:8080))
# ...
```

Supports a development webserver which is slower but allows an easy edit/save/reload development process. It uses the [PHP development server](http://php.net/features.commandline.webserver) in the background; PHP code is recompiled and application setup performed from scratch on every request.

```bash
$ xp -supervise web -m develop Service
@xp.web.Develop(HTTP @ `php -S localhost:8080 -t /home/example/devel/shorturl`)
# ...
```

Now open the website at http://localhost:8080/hello

Performance
-----------
Because the code for the web application is only compiled once when using production servers, we achieve lightning-fast request/response roundtrip times:

![Network console screenshot](https://user-images.githubusercontent.com/696742/114266967-99224b00-99f9-11eb-928b-e10efd5fd7bd.png)

See also
--------
This library provides for the very basic functionality. To create web frontends or REST APIs, have a look at the following libraries built ontop of this:

* [Web frontents](https://github.com/xp-forge/frontend)
* [REST APIs](https://github.com/xp-forge/rest-api)