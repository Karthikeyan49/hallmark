<?php
declare(strict_types=1);

namespace App\Core;

/** Very small PHP-template view renderer with a shared layout. */
final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        $content = self::partial($view, $data);

        if ($layout === '') {
            return $content;
        }

        return self::partial($layout, array_merge($data, ['content' => $content]));
    }

    /** Render a single template file to a string. */
    public static function partial(string $view, array $data = []): string
    {
        $file = BASE_PATH . '/views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }
}
