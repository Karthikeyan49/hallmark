<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** The admin user model. */
final class User
{
    public static function count(): int
    {
        return (int) Database::conn()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /** @return array<string,mixed>|null */
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::conn()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $username, string $passwordHash): int
    {
        $stmt = Database::conn()->prepare(
            'INSERT INTO users (username, password_hash) VALUES (?, ?)'
        );
        $stmt->execute([$username, $passwordHash]);
        return (int) Database::conn()->lastInsertId();
    }
}
