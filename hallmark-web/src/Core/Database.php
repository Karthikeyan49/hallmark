<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/** SQLite/PDO connection holder + schema migration. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function init(string $path): void
    {
        self::$pdo = new PDO('sqlite:' . $path);
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$pdo->exec('PRAGMA foreign_keys = ON');
    }

    public static function conn(): PDO
    {
        if (self::$pdo === null) {
            throw new \RuntimeException('Database not initialised.');
        }
        return self::$pdo;
    }

    public static function migrate(): void
    {
        $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
        if ($schema !== false) {
            self::conn()->exec($schema);
        }
    }
}
