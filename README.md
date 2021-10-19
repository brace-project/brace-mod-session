[![Action Status](https://github.com/brace-project/brace-mod-session/workflows/test/badge.svg)](https://github.com/brace-project/brace-mod-session/actions)
[![Latest Stable Version](https://poser.pugx.org/brace/mod-session/v)](//packagist.org/packages/brace/mod-session)
[![Total Downloads](https://poser.pugx.org/brace/mod-session/downloads)](//packagist.org/packages/brace/mod-session)
[![License](https://poser.pugx.org/brace/mod-session/license)](//packagist.org/packages/brace/mod-session)


# brace-mod-session

Session middleware for Brace Core Applications

### Installation

```sh
composer require lack/mfdk
```

### Usage

You can use the `Brace\Session\SessionMiddleware` in any
[Brace Core Application](https://github.com/brace-project/brace-core).

this would look like following:

```php
\Brace\Core\AppLoader::extend(function (\Brace\Core\BraceApp $app) {
    (/*.....*/)
    $app->setPipe([
        new \Brace\Session\SessionMiddleware(
            new \Brace\Session\Storages\FileSessionStorage("/tmp"), // replace this with your chosen storage type and connection string
            3600, // 1 hour ttl
            86400 // 1 day expiration time
        ),
        (/*.....*/)
    ]);
});
```

After this, you can access the session data inside any route/middleware that
has access to the `\Brace\Core\BraceApp` :

```php
AppLoader::extend(function (BraceApp $app) {
    $app->router->on("GET@/", function() use ($app) {
        $session = $app->get(SessionMiddleware::SESSION_ATTRIBUTE);
        $session->set('foo', 'bar');
        (/*....*/)
        return $response;
    });
});

```

### Examples

### Contributing

Please refer to the [contributing notes](CONTRIBUTING.md).

### License

This project is made public under the [MIT LICENSE](LICENSE)
