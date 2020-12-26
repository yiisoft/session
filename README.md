<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Session</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/yiisoft/session/v/stable.png)](https://packagist.org/packages/yiisoft/session)
[![Total Downloads](https://poser.pugx.org/yiisoft/session/downloads.png)](https://packagist.org/packages/yiisoft/session)
[![Build status](https://github.com/yiisoft/session/workflows/build/badge.svg)](https://github.com/yiisoft/session/actions?query=workflow%3Abuild)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/session/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/session/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/session/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/session/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fsession%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/session/master)
[![static analysis](https://github.com/yiisoft/session/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/session/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/session/coverage.svg)](https://shepherd.dev/github/yiisoft/session)

The package implements a session service, [PSR-15](https://www.php-fig.org/psr/psr-15/) session middleware,
and a flash message service which helps use one-time messages.

## Installation

The package could be installed with composer:

```
composer install yiisoft/session
```

In order to maintain a session between requests you need to add `SessionMiddleware` to your main middleware stack.
In Yii it is done by configuring `MiddlewareDispatcher`:

```php
return [
    MiddlewareDispatcher::class => static fn (ContainerInterface $container) => (new MiddlewareDispatcher($container))
        ->addMiddleware($container->get(Router::class))
        ->addMiddleware($container->get(SessionMiddleware::class)) // <-- here
        ->addMiddleware($container->get(CsrfMiddleware::class))
        ->addMiddleware($container->get(ErrorCatcher::class)),
];
```

## General usage

You can access session data through `SessionInterface`.

```php
/** @var \Yiisoft\Session\SessionInterface $session */
$myId = $session->get('my_id');
if ($myId === null) {
    $session->set('my_id', 42);
}
```

In case you need some data to remain in session until read, such as in case with displaying a message on the next page,
`FlashInteface` is your friend:

```php
/** @var Yiisoft\Session\Flash\FlashInterface $flash */

// request 1
$flash->set('warning', 'Oh no, not again.');

// request 2
$warning = $flash->get('warning');
if ($warning !== null) {
    // do something with it
}
```

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```php
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
./vendor/bin/infection
```

## Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```php
./vendor/bin/psalm
```
