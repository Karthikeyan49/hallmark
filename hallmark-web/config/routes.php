<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ItemController;
use App\Core\Router;

/** Route table. Returns a closure that registers routes on the router. */
return function (Router $router): void {
    // Auth
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
    $router->post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    $router->get('/', [DashboardController::class, 'index']);

    // Compliance items
    $router->get('/items', [ItemController::class, 'index']);
    $router->get('/items/new', [ItemController::class, 'create']);
    $router->post('/items', [ItemController::class, 'store']);
    $router->get('/items/{id}', [ItemController::class, 'show']);
    $router->get('/items/{id}/composite', [ItemController::class, 'composite']);
};
