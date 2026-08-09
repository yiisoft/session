<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/config', isDev: false)
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    ->ignoreErrorsOnExtension('ext-session', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackages(['psr/http-message-implementation'], [ErrorType::UNUSED_DEPENDENCY]);

// The `ReturnTypeWillChange` attribute used in tests is native since PHP 8.1 and is polyfilled by
// symfony/polyfill-php81 (pulled in transitively) on PHP 8.0, so it only shows up as a shadow
// dependency when running under PHP 8.0.
if (PHP_VERSION_ID < 80100) {
    $config->ignoreErrorsOnPackages(['symfony/polyfill-php81'], [ErrorType::SHADOW_DEPENDENCY]);
}

return $config;
