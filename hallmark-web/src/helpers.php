<?php
declare(strict_types=1);

/**
 * Global view/helper functions. Included from the front controller so they are
 * available inside templates (which run in the global namespace).
 */

use App\Core\Session;

if (!function_exists('e')) {
    /** HTML-escape a value for safe output. */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_field')) {
    /** Hidden CSRF input for forms. */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(Session::csrfToken()) . '">';
    }
}

if (!function_exists('money_inr')) {
    /** Format an INR amount. */
    function money_inr(float $amount): string
    {
        return '₹' . number_format($amount, 2);
    }
}
