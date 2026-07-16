<?php
declare(strict_types=1);

namespace App\Core;

use App\Support\Auth;

/**
 * Application bootstrapper: wires configuration, session, database and the
 * router, then dispatches the current request.
 */
final class App
{
    public function __construct(private array $config)
    {
        Config::set($config);
    }

    public function run(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        Session::start();
        Database::init($this->config['db_path']);
        Database::migrate();
        Auth::seedAdmin($this->config);

        $router = new Router();
        (require BASE_PATH . '/config/routes.php')($router);

        $router->dispatch(new Request());
    }
}
