<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
if (!is_admin_logged_in()) {
    header('Location: ' . APP_BASE_URL . '/admin/login.php');
    exit;
}
header('Location: ' . APP_BASE_URL . '/admin/dashboard.php');
exit;


