<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool
    {
        self::start();
        return isset($_SESSION['user_id']);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function user(): ?array
    {
        self::start();
        if (!self::check()) {
            return null;
        }

        return [
            'id'       => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? '',
            'name'     => $_SESSION['user_name'] ?? '',
            'email'    => $_SESSION['user_email'] ?? '',
        ];
    }

    public static function attempt(string $username, string $password): bool
    {
        self::start();
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id, name, username, email, password FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => trim($username)]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['user_name']  = $user['name'] ?: $user['username'];
            $_SESSION['user_email'] = $user['email'];
            return true;
        }

        return false;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrf(?string $token): bool
    {
        self::start();
        return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
