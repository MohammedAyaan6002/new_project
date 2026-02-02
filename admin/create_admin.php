<?php
/**
 * One-time setup: create admin user. Run from CLI only for security.
 * Default: admin@example.com / admin123 (change in production)
 * From web: use sql/add_admin_user.sql instead.
 */
require_once __DIR__ . '/../includes/db.php';

$email = 'admin@example.com';
$password = 'admin123';
$name = 'Admin';

if (php_sapi_name() !== 'cli') {
    die('Run from CLI only: php admin/create_admin.php');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("INSERT INTO users (name, email, role, password_hash) VALUES (?, ?, 'admin', ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'admin'");
$stmt->bind_param('sss', $name, $email, $hash);
if ($stmt->execute()) {
    echo "Admin user ready: $email / $password — change password after first login.\n";
} else {
    echo "Error: " . $mysqli->error . "\n";
}
