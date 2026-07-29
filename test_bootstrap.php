<?php

/**
 * Bootstrap autoloader for running domain unit tests without full Composer install.
 * Loads domain classes (App\Domain) and test classes (Tests\Unit\Domain).
 */

// Load Composer's autoloader first (provides ramsey/uuid etc.)
$loader = require __DIR__ . '/vendor/autoload.php';

// Register PSR-4 for test classes
spl_autoload_register(function (string $class): void {
    // Tests\ prefix -> tests/
    $testsPrefix = 'Tests\\';
    if (str_starts_with($class, $testsPrefix)) {
        $relativeClass = substr($class, strlen($testsPrefix));
        $file = __DIR__ . '/tests/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }

    // App\Domain prefix -> app/Domain (already covered by Composer's autoload,
    // but let's be safe)
    $appDomainPrefix = 'App\\Domain\\';
    if (str_starts_with($class, $appDomainPrefix)) {
        $relativeClass = substr($class, strlen('App\\'));
        $file = __DIR__ . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});
