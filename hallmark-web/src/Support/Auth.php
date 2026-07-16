<?php
declare(strict_types=1);

namespace App\Support;

use App\Core\Session;
use App\Models\User;

/** Authentication for the single prototype admin. */
final class Auth
{
    /** Create the admin account on first boot if the users table is empty. */
    public static function seedAdmin(array $config): void
    {
        if (User::count() > 0) {
            return;
        }
        User::create(
            $config['admin_username'],
            password_hash($config['admin_password'], PASSWORD_DEFAULT)
        );
    }

    public static function attempt(string $username, string $password): bool
    {
        $user = User::findByUsername($username);
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Session::set('username', $user['username']);
        return true;
    }

    public static function check(): bool
    {
        return Session::get('user_id') !== null;
    }

    public static function username(): ?string
    {
        return Session::get('username');
    }

    public static function logout(): void
    {
        Session::forget('user_id');
        Session::forget('username');
        Session::regenerate();
    }
}
