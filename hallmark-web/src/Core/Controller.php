<?php
declare(strict_types=1);

namespace App\Core;

use App\Support\Auth;

/** Base controller with view, redirect, auth-guard and CSRF helpers. */
abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        return View::render($view, $data, $layout);
    }

    protected function redirect(string $path): string
    {
        header('Location: ' . $path);
        return '';
    }

    /** Redirect to the login page when no admin is signed in. */
    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            Session::flash('error', 'Please sign in to continue.');
            header('Location: /login');
            exit;
        }
    }

    /** Abort POST handling if the CSRF token is missing/invalid. */
    protected function requireCsrf(Request $request): void
    {
        if (!Session::verifyCsrf($request->input('_csrf'))) {
            http_response_code(419);
            Session::flash('error', 'Your session expired. Please try again.');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
            exit;
        }
    }
}
