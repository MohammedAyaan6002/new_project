<?php
/**
 * Admin authentication helpers.
 * Requires db.php and helpers.php to be loaded first.
 */

if (!function_exists('is_admin_logged_in')) {
    function is_admin_logged_in(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['admin_user_id']) && !empty($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin';
    }
}

if (!function_exists('require_admin')) {
    function require_admin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!is_admin_logged_in()) {
            if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
                || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Admin login required']);
                exit;
            }
            header('Location: ' . (defined('APP_BASE_URL') ? APP_BASE_URL : '') . '/admin/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
            exit;
        }
    }
}

if (!function_exists('admin_user')) {
    function admin_user(): ?array
    {
        if (!is_admin_logged_in()) {
            return null;
        }
        return [
            'id' => $_SESSION['admin_user_id'] ?? null,
            'email' => $_SESSION['admin_email'] ?? null,
            'name' => $_SESSION['admin_name'] ?? null,
        ];
    }
}

if (!function_exists('admin_csrf_token')) {
    function admin_csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['admin_csrf_token'])) {
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['admin_csrf_token'];
    }
}

if (!function_exists('verify_admin_csrf')) {
    function verify_admin_csrf(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['admin_csrf_token']) && hash_equals($_SESSION['admin_csrf_token'], $token);
    }
}
