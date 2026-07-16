<?php
declare(strict_types=1);

/**
 * Front controller — the single entry point for the whole application.
 * Every request is routed through here (MVC front-controller pattern).
 */

define('BASE_PATH', dirname(__DIR__));

// Let the PHP built-in server serve real static files (css, images) directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

// Minimal PSR-4 autoloader: App\Foo\Bar -> src/Foo/Bar.php
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $path = BASE_PATH . '/src/' . $relative . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

require BASE_PATH . '/src/helpers.php';

$config = require BASE_PATH . '/config/config.php';

(new App\Core\App($config))->run();
